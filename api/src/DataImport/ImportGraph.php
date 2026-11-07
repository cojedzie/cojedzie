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
use App\Event\ImportFinishedEvent;
use App\Event\ImportStartedEvent;
use App\Message\RunImportTask;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Ds\Map;
use Kadet\Functional\Collections as c;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * The import as a persisted graph of tasks, one per importer, with the importers' dependencies. Workers run the
 * tasks as RunImportTask messages (see ImportTaskRunner), so several of them can work on one import.
 *
 * Every change of an import's or task's status that two workers could race for is a single UPDATE checked by the
 * number of rows it changed. An import that starts or ends is announced with ImportStartedEvent and
 * ImportFinishedEvent.
 */
class ImportGraph
{
    /**
     * @var list<RunImportTask>|null messages while running synchronously, null when they go to workers
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
        private readonly EventDispatcherInterface $events,
        private readonly ClockInterface $clock,
        #[AutowireServiceClosure(ImportTaskRunner::class)]
        private readonly \Closure $runner,
    ) {
    }

    /**
     * Supersedes the unfinished imports, creates a new one with a task per importer and dispatches the tasks that
     * depend on nothing.
     *
     * @throws \LogicException when importers depend on missing importers or on each other, before anything is written
     */
    public function start(): ImportEntity
    {
        [$import, $superseded] = $this->create($this->resolve());

        foreach ($superseded as $previous) {
            $this->events->dispatch(new ImportFinishedEvent($previous));
        }
        $this->events->dispatch(new ImportStartedEvent($import));

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
            $this->send(new RunImportTask($taskId));
        }
    }

    /**
     * Finishes the import when no task is left to run: it succeeded when every task was done, else it failed. Only the
     * one caller that finishes it announces it.
     */
    public function finishIfComplete(ImportEntity $import): void
    {
        if ($this->countTasksIn($import, ImportTaskStatus::unfinished()) > 0) {
            return;
        }

        $failed = $this->countTasksIn($import, ImportTaskStatus::notSucceeded()) > 0;
        if ($this->claimEnd($import, $failed ? ImportStatus::Failed : ImportStatus::Succeeded)) {
            $this->events->dispatch(new ImportFinishedEvent($import));
        }
    }

    /**
     * Runs the whole import in this process, without workers.
     */
    public function runSynchronously(): ImportEntity
    {
        $this->queue = [];

        try {
            $import = $this->start();

            $this->runQueuedMessages();

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
        $rows = $this
            ->query(
                'SELECT t.status, COUNT(t.id) AS tasks FROM App\Entity\ImportTaskEntity t
                 WHERE t.import = :import GROUP BY t.status ORDER BY t.status',
                ['import' => $import->getId()],
            )
            ->getArrayResult();

        return $rows
            |> c\keyBy(fn (array $row) => $row['status']->value)
            |> c\map(fn (array $row) => (int) $row['tasks'])
            |> c\toArray();
    }

    /**
     * Supersedes the unfinished imports and creates the new one with its tasks, in one transaction.
     *
     * @param array<string, array{DataImporter, list<string>}> $importers
     *
     * @return array{ImportEntity, list<ImportEntity>} the new import, and the imports it superseded
     */
    private function create(array $importers): array
    {
        return $this->em->getConnection()->transactional(function () use ($importers): array {
            $now = $this->now();

            $superseded = $this->supersede($now);

            $import = $this->persistImport($now);
            $this->persistTasks($import, $importers);

            $this->em->flush();

            return [$import, $superseded];
        });
    }

    private function persistImport(Carbon $startedAt): ImportEntity
    {
        $import = new ImportEntity();
        $import->setStartedAt($startedAt);

        $this->em->persist($import);

        return $import;
    }

    /**
     * @param array<string, array{DataImporter, list<string>}> $importers in dependency order, so each task's
     *                                                                     dependencies already have their ids
     */
    private function persistTasks(ImportEntity $import, array $importers): void
    {
        /** @var array<string, ImportTaskEntity> $tasks by importer service id */
        $tasks = [];
        foreach ($importers as $key => [$importer, $dependencies]) {
            $tasks[$key] = new ImportTaskEntity(
                import: $import,
                importer: $key,
                description: $importer->getDescription(),
                dependencies: $dependencies |> c\map(fn (string $dependency) => $tasks[$dependency]->getId()->toRfc4122()),
            );

            $this->em->persist($tasks[$key]);
        }
    }

    /**
     * Ends the unfinished imports, so a stalled one can't block the import forever. Their tasks still running finish,
     * but nothing more runs.
     *
     * @return list<ImportEntity> the imports this call ended, an import finishing meanwhile isn't one of them
     */
    private function supersede(Carbon $now): array
    {
        return $this->unfinishedImports()
            |> c\filter(fn (ImportEntity $import) => $this->supersedeImport($import, $now))
            |> c\values();
    }

    /**
     * @return list<ImportEntity>
     */
    private function unfinishedImports(): array
    {
        return $this
            ->query('SELECT i FROM App\Entity\ImportEntity i WHERE i.status = :running', ['running' => ImportStatus::Running])
            ->setHint(Query::HINT_REFRESH, true)
            ->getResult();
    }

    /**
     * @return bool whether this call ended the import
     */
    private function supersedeImport(ImportEntity $import, Carbon $now): bool
    {
        $this->skipUnstartedTasks($import, $now);

        return $this->claimEnd($import, ImportStatus::Superseded, $now);
    }

    private function skipUnstartedTasks(ImportEntity $import, Carbon $now): void
    {
        $this
            ->query(
                'UPDATE App\Entity\ImportTaskEntity t SET t.status = :skipped, t.finishedAt = :now
                 WHERE t.import = :import AND t.status IN (:unstarted)',
                [
                    'skipped'   => ImportTaskStatus::Skipped,
                    'now'       => $now,
                    'import'    => $import->getId(),
                    'unstarted' => ImportTaskStatus::unstarted(),
                ],
            )
            ->execute();
    }

    /**
     * Ends a running import with the status.
     *
     * @return bool whether this call ended it, false when another one already did
     */
    private function claimEnd(ImportEntity $import, ImportStatus $status, ?Carbon $now = null): bool
    {
        $now ??= $this->now();

        $changed = $this
            ->query(
                'UPDATE App\Entity\ImportEntity i SET i.status = :status, i.finishedAt = :now WHERE i.id = :id AND i.status = :running',
                ['status' => $status, 'now' => $now, 'id' => $import->getId(), 'running' => ImportStatus::Running],
            )
            ->execute();

        if ($changed !== 1) {
            return false;
        }

        $import->setStatus($status);
        $import->setFinishedAt($now);

        return true;
    }

    /**
     * Marks ready pending tasks queued and skips those that can't run anymore. A skipped task skips its dependents
     * too, so this repeats until nothing more was skipped.
     *
     * @return list<string> ids of the tasks this call queued
     */
    private function claimReady(ImportEntity $import): array
    {
        $now     = $this->now();
        $claimed = [];

        do {
            $pending = $this->dependencyStatusesOfPendingTasks($import);

            $doomed = $pending |> c\filter(fn (array $dependencies) => $dependencies |> c\any(fn (ImportTaskStatus $status) => $status->hasNotSucceeded()));
            $ready  = $pending |> c\filter(fn (array $dependencies) => $dependencies |> c\every(fn (ImportTaskStatus $status) => $status === ImportTaskStatus::Done));

            $skipped = $this->skipPendingTasks($doomed |> c\keys(), $now);
            $claimed = [...$claimed, ...$this->queuePendingTasks($ready |> c\keys())];
        } while ($skipped);

        return $claimed;
    }

    /**
     * @return Map<string, list<ImportTaskStatus>> statuses of the dependencies of each pending task, by task id; a
     *                                             dependency that isn't in the graph counts as skipped
     */
    private function dependencyStatusesOfPendingTasks(ImportEntity $import): Map
    {
        $tasks    = $this->tasksOf($import) |> c\keyBy(fn (ImportTaskEntity $task) => $task->getId()->toRfc4122());
        $statuses = $tasks |> c\map(fn (ImportTaskEntity $task) => $task->getStatus());

        return $tasks
            |> c\filter(fn (ImportTaskEntity $task) => $task->getStatus() === ImportTaskStatus::Pending)
            |> c\map(fn (ImportTaskEntity $task) => $task->getDependencies()
                |> c\map(fn (string $dependency) => $statuses->get($dependency, ImportTaskStatus::Skipped)));
    }

    /**
     * @return list<ImportTaskEntity> as they are now: other workers change them with UPDATE statements
     */
    private function tasksOf(ImportEntity $import): array
    {
        return $this
            ->query('SELECT t FROM App\Entity\ImportTaskEntity t WHERE t.import = :import ORDER BY t.id', ['import' => $import->getId()])
            ->setHint(Query::HINT_REFRESH, true)
            ->getResult();
    }

    /**
     * @param iterable<string> $taskIds
     *
     * @return bool whether this call skipped any of them
     */
    private function skipPendingTasks(iterable $taskIds, Carbon $now): bool
    {
        $skipped = $taskIds |> c\map(fn (string $taskId) => $this->skipPendingTask(Uuid::fromString($taskId), $now));

        return $skipped |> c\contains(value: true);
    }

    private function skipPendingTask(Uuid $taskId, Carbon $now): bool
    {
        $changed = $this
            ->query(
                'UPDATE App\Entity\ImportTaskEntity t SET t.status = :skipped, t.finishedAt = :now WHERE t.id = :id AND t.status = :pending',
                ['skipped' => ImportTaskStatus::Skipped, 'now' => $now, 'id' => $taskId, 'pending' => ImportTaskStatus::Pending],
            )
            ->execute();

        return $changed === 1;
    }

    /**
     * @param iterable<string> $taskIds
     *
     * @return list<string> ids of the tasks this call queued
     */
    private function queuePendingTasks(iterable $taskIds): array
    {
        return $taskIds
            |> c\filter(fn (string $taskId) => $this->queuePendingTask(Uuid::fromString($taskId)))
            |> c\toArray();
    }

    private function queuePendingTask(Uuid $taskId): bool
    {
        $changed = $this
            ->query(
                'UPDATE App\Entity\ImportTaskEntity t SET t.status = :queued WHERE t.id = :id AND t.status = :pending',
                ['queued' => ImportTaskStatus::Queued, 'id' => $taskId, 'pending' => ImportTaskStatus::Pending],
            )
            ->execute();

        return $changed === 1;
    }

    /**
     * @param list<ImportTaskStatus> $statuses
     */
    private function countTasksIn(ImportEntity $import, array $statuses): int
    {
        return (int) $this
            ->query(
                'SELECT COUNT(t.id) FROM App\Entity\ImportTaskEntity t WHERE t.import = :import AND t.status IN (:statuses)',
                ['import' => $import->getId(), 'statuses' => $statuses],
            )
            ->getSingleScalarResult();
    }

    /**
     * Importers in an order where each comes after its dependencies.
     *
     * @return array<string, array{DataImporter, list<string>}> importer and ids of its dependencies, by service id
     */
    private function resolve(): array
    {
        $importers = $this->importersByServiceId();

        return $this->inDependencyOrder($importers, $this->dependenciesByServiceId($importers))
            |> c\map(fn (array $dependencies, string $id) => [$importers[$id], $dependencies]);
    }

    /**
     * @return array<string, DataImporter>
     */
    private function importersByServiceId(): array
    {
        return $this->importers->getProvidedServices()
            |> c\map(fn (string $type, string $id) => $this->importers->get($id));
    }

    /**
     * Importers name their dependencies by class; a class that isn't a service is kept, it never resolves.
     *
     * @param array<string, DataImporter> $importers
     *
     * @return array<string, list<string>> service ids of each importer's dependencies, by service id
     */
    private function dependenciesByServiceId(array $importers): array
    {
        $ids = array_flip($importers |> c\map(fn (DataImporter $importer) => $importer::class));

        return $importers
            |> c\map(fn (DataImporter $importer) => $importer->getDependencies()
                |> c\map(fn (string $class) => $ids[$class] ?? $class));
    }

    /**
     * Sorts the graph topologically: takes the importers whose dependencies are all taken, until none is left.
     *
     * @param array<string, DataImporter> $importers
     * @param array<string, list<string>> $pending   dependencies by service id
     *
     * @return array<string, list<string>> the same, each after its dependencies
     */
    private function inDependencyOrder(array $importers, array $pending): array
    {
        $ordered = [];
        while ($pending !== []) {
            $ready = $pending |> c\filter(fn (array $dependencies) => array_diff($dependencies, array_keys($ordered)) === []);

            if ($ready === []) {
                throw new \LogicException(sprintf(
                    'Importers %s depend on importers that are missing or depend on them.',
                    $pending |> c\keys() |> c\map(fn (string $id) => $importers[$id]::class) |> c\join(', '),
                ));
            }

            $ordered += $ready |> c\map(array_values(...));
            $pending = array_diff_key($pending, $ready);
        }

        return $ordered;
    }

    private function runQueuedMessages(): void
    {
        while (($message = array_shift($this->queue)) !== null) {
            ($this->runner)()->run($message->taskId);
        }
    }

    private function send(RunImportTask $message): void
    {
        if ($this->queue !== null) {
            $this->queue[] = $message;
        } else {
            $this->bus->dispatch($message);
        }
    }

    /**
     * @param array<string, mixed> $parameters ids (Uuid) are bound as uuid, DQL can't tell their type
     */
    private function query(string $dql, array $parameters): Query
    {
        $query = $this->em->createQuery($dql);
        foreach ($parameters as $name => $value) {
            $query->setParameter($name, $value, $value instanceof Uuid ? 'uuid' : null);
        }

        return $query;
    }

    private function now(): Carbon
    {
        return Carbon::instance($this->clock->now());
    }
}
