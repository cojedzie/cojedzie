<?php
/*
 * Copyright (C) 2026 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace App\DataImport;

use App\Entity\ImportEntity;
use App\Entity\ImportTaskEntity;
use App\Message\RunImportTask;
use Carbon\Carbon;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * The import as a persisted graph of tasks, one per importer, with the importers' dependencies. Workers run the
 * tasks as RunImportTask messages (see ImportTaskRunner), so several of them can work on one import.
 *
 * Every change of a task's status that two workers could race for is a single UPDATE checked by the number of
 * rows it changed.
 */
class ImportGraph
{
    /**
     * @var list<string>|null ids of claimed tasks while running synchronously, null when they go to workers
     */
    private ?array $queue = null;

    /**
     * @param ServiceProviderInterface<DataImporter> $importers by service id
     * @param \Closure(): ImportTaskRunner           $runner    for runSynchronously(), the runner needs the graph
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ServiceProviderInterface $importers,
        private readonly MessageBusInterface $bus,
        private readonly ClockInterface $clock,
        #[AutowireServiceClosure(ImportTaskRunner::class)]
        private readonly \Closure $runner,
    ) {
    }

    /**
     * Supersedes an unfinished import, creates a new one with a task per importer and dispatches the tasks that
     * depend on nothing.
     *
     * @param ?string $checkInId Sentry check-in to report when the import finishes
     *
     * @throws \LogicException when importers depend on missing importers or on each other, before anything is written
     */
    public function start(?string $checkInId = null): ImportEntity
    {
        $importers = $this->resolve();

        $import = $this->em->wrapInTransaction(function () use ($importers, $checkInId): ImportEntity {
            $now = $this->now();

            $this->supersede($now);

            $import = new ImportEntity();
            $import->setStartedAt($now);
            $import->setCheckInId($checkInId);
            $this->em->persist($import);

            /** @var array<string, ImportTaskEntity> $tasks */
            $tasks = [];
            foreach ($importers as $key => [$importer, $dependencies]) {
                $tasks[$key] = new ImportTaskEntity(
                    import: $import,
                    importer: $key,
                    description: $importer->getDescription(),
                    dependencies: array_map(fn (string $dependency) => $tasks[$dependency]->getId()->toRfc4122(), $dependencies),
                );

                $this->em->persist($tasks[$key]);
            }

            $this->em->flush();

            return $import;
        });

        $this->dispatchReady($import);

        return $import;
    }

    /**
     * Dispatches the pending tasks whose dependencies are done; those depending on a failed or skipped task are
     * skipped. Called whenever a task ends, so by several workers at once.
     */
    public function dispatchReady(ImportEntity $import): void
    {
        foreach ($this->claimReady($import) as $taskId) {
            if ($this->queue !== null) {
                $this->queue[] = $taskId;
            } else {
                $this->bus->dispatch(new RunImportTask($taskId));
            }
        }
    }

    /**
     * Finishes the import when no task is left to run. It succeeded when every task but the spawned ones was done.
     *
     * @return bool true only for the one caller that finished it
     */
    public function finishIfComplete(ImportEntity $import): bool
    {
        $unfinished = (int) $this->connection()->fetchOne(
            "SELECT COUNT(*) FROM import_task WHERE import_id = :import AND status IN ('pending', 'queued', 'running')",
            ['import' => $import->getId()],
            ['import' => 'uuid'],
        );

        if ($unfinished > 0) {
            return false;
        }

        // spawned tasks are the ones with a payload, their failure is retried by the next import
        $failed = (int) $this->connection()->fetchOne(
            "SELECT COUNT(*) FROM import_task WHERE import_id = :import AND payload IS NULL AND status IN ('failed', 'skipped')",
            ['import' => $import->getId()],
            ['import' => 'uuid'],
        );

        $now       = $this->now();
        $succeeded = $failed === 0;

        $changed = $this->connection()->executeStatement(
            'UPDATE import SET finished_at = :now, succeeded = :succeeded WHERE id = :id AND finished_at IS NULL',
            ['now' => $now, 'succeeded' => $succeeded, 'id' => $import->getId()],
            ['now' => 'datetime', 'succeeded' => Types::BOOLEAN, 'id' => 'uuid'],
        );

        if ($changed !== 1) {
            return false;
        }

        $import->setFinishedAt($now);
        $import->setSucceeded($succeeded);

        return true;
    }

    /**
     * Runs the whole import in this process, spawned tasks included, without workers.
     */
    public function runSynchronously(): ImportEntity
    {
        $this->queue = [];

        try {
            $import = $this->start();

            while (($taskId = array_shift($this->queue)) !== null) {
                ($this->runner)()->run($taskId);
            }

            // an import without tasks has no task to finish it
            $this->finishIfComplete($import);
        } finally {
            $this->queue = null;
        }

        return $import;
    }

    /**
     * @return array<string, int> number of tasks of the import, by status
     */
    public function countTasks(ImportEntity $import): array
    {
        return array_map(intval(...), $this->connection()->fetchAllKeyValue(
            'SELECT status, COUNT(*) FROM import_task WHERE import_id = :import GROUP BY status ORDER BY status',
            ['import' => $import->getId()],
            ['import' => 'uuid'],
        ));
    }

    /**
     * Ends unfinished imports, so a stalled one can't block the import forever. Their tasks still running finish,
     * but nothing more runs.
     */
    private function supersede(Carbon $now): void
    {
        $this->connection()->executeStatement(
            "UPDATE import_task SET status = 'skipped', finished_at = :now
             WHERE status IN ('pending', 'queued') AND import_id IN (SELECT id FROM import WHERE finished_at IS NULL)",
            ['now' => $now],
            ['now' => 'datetime'],
        );

        $this->connection()->executeStatement(
            'UPDATE import SET finished_at = :now, succeeded = :succeeded WHERE finished_at IS NULL',
            ['now' => $now, 'succeeded' => false],
            ['now' => 'datetime', 'succeeded' => Types::BOOLEAN],
        );
    }

    /**
     * Marks ready pending tasks queued, skips those that can't run anymore.
     *
     * @return list<string> ids of the tasks this call queued
     */
    private function claimReady(ImportEntity $import): array
    {
        $connection = $this->connection();
        $now        = $this->now();

        $finished = $connection->fetchOne(
            'SELECT finished_at FROM import WHERE id = :import',
            ['import' => $import->getId()],
            ['import' => 'uuid'],
        );

        // tasks spawned by a task of a superseded import
        if ($finished !== null) {
            $connection->executeStatement(
                "UPDATE import_task SET status = 'skipped', finished_at = :now WHERE import_id = :import AND status = 'pending'",
                ['now' => $now, 'import' => $import->getId()],
                ['now' => 'datetime', 'import' => 'uuid'],
            );

            return [];
        }

        $claimed = [];
        do {
            $skipped = false;

            // only the importers' own tasks are dependencies, spawned ones matter only while pending
            $rows = $connection->fetchAllAssociative(
                "SELECT id, status, dependencies FROM import_task WHERE import_id = :import AND (payload IS NULL OR status = 'pending')",
                ['import' => $import->getId()],
                ['import' => 'uuid'],
            );

            $statuses = [];
            foreach ($rows as $row) {
                $statuses[Uuid::fromString($row['id'])->toRfc4122()] = ImportTaskStatus::from($row['status']);
            }

            foreach ($rows as $row) {
                if ($row['status'] !== ImportTaskStatus::Pending->value) {
                    continue;
                }

                $dependencies = array_map(
                    fn (string $id) => $statuses[$id] ?? ImportTaskStatus::Skipped,
                    json_decode((string) $row['dependencies'], true, flags: JSON_THROW_ON_ERROR),
                );

                $id = Uuid::fromString($row['id']);

                if (array_any($dependencies, fn (ImportTaskStatus $status) => in_array($status, [ImportTaskStatus::Failed, ImportTaskStatus::Skipped], true))) {
                    $skipped = $connection->executeStatement(
                        "UPDATE import_task SET status = 'skipped', finished_at = :now WHERE id = :id AND status = 'pending'",
                        ['now' => $now, 'id' => $id],
                        ['now' => 'datetime', 'id' => 'uuid'],
                    ) === 1 || $skipped;
                } elseif (array_all($dependencies, fn (ImportTaskStatus $status) => $status === ImportTaskStatus::Done)) {
                    $queued = $connection->executeStatement(
                        "UPDATE import_task SET status = 'queued' WHERE id = :id AND status = 'pending'",
                        ['id' => $id],
                        ['id' => 'uuid'],
                    );

                    if ($queued === 1) {
                        $claimed[] = $id->toRfc4122();
                    }
                }
            }
            // a skipped task skips its dependents too
        } while ($skipped);

        return $claimed;
    }

    /**
     * Importers in an order where each comes after its dependencies.
     *
     * @return array<string, array{DataImporter, list<string>}> importer and ids of its dependencies, by service id
     */
    private function resolve(): array
    {
        $importers = [];
        $ids       = [];
        foreach (array_keys($this->importers->getProvidedServices()) as $id) {
            $importers[$id]              = $this->importers->get($id);
            $ids[$importers[$id]::class] = $id;
        }

        $pending = [];
        foreach ($importers as $id => $importer) {
            $pending[$id] = array_map(fn (string $class) => $ids[$class] ?? $class, $importer->getDependencies());
        }

        $ordered = [];
        while ($pending !== []) {
            $ready = array_filter($pending, fn (array $dependencies) => array_diff($dependencies, array_keys($ordered)) === []);

            if ($ready === []) {
                throw new \LogicException(sprintf(
                    'Importers %s depend on importers that are missing or depend on them.',
                    implode(', ', array_map(fn (string $id) => $importers[$id]::class, array_keys($pending))),
                ));
            }

            foreach ($ready as $id => $dependencies) {
                $ordered[$id] = [$importers[$id], array_values($dependencies)];
                unset($pending[$id]);
            }
        }

        return $ordered;
    }

    private function connection(): Connection
    {
        return $this->em->getConnection();
    }

    private function now(): Carbon
    {
        return Carbon::instance($this->clock->now());
    }
}
