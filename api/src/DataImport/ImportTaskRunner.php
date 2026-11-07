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
use App\Event\DataUpdateEvent;
use Carbon\Carbon;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * Runs one task of the import graph: its importer, then the tasks it spawned and the tasks that became ready.
 */
class ImportTaskRunner
{
    /**
     * Measured on 2026-10-28 (a full day of ZTM data, in one task): 48 MB peak without debug, 906 MB with it, as
     * Doctrine keeps every query for the profiler. The import runs in debug through `task api:update`.
     */
    private const string MEMORY_LIMIT = '2G';

    /**
     * @param ServiceProviderInterface<DataImporter> $importers by service id
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ServiceProviderInterface $importers,
        private readonly ImportGraph $graph,
        private readonly ProgressReporterFactory $progressReporterFactory,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * Runs the task, unless another delivery of it already did. Whatever the importer throws fails the task, and
     * its dependents are skipped; it isn't retried, the next import plans it again.
     *
     * @return ?ImportEntity the import, when this task finished it
     */
    public function run(string $taskId): ?ImportEntity
    {
        $connection = $this->em->getConnection();
        $id         = Uuid::fromString($taskId);

        $claimed = $connection->executeStatement(
            "UPDATE import_task SET status = 'running', started_at = :now WHERE id = :id AND status = 'queued'",
            [
                'now' => $this->now(),
                'id'  => $id,
            ],
            [
                'now' => 'datetime',
                'id'  => 'uuid',
            ],
        );

        if ($claimed !== 1) {
            return null;
        }

        $task = $connection->fetchAssociative(
            'SELECT import_id, importer, payload, description FROM import_task WHERE id = :id',
            ['id' => $id],
            ['id' => 'uuid'],
        );

        $import   = $this->em->find(ImportEntity::class, Uuid::fromString($task['import_id']));
        $payload  = $task['payload'] === null ? null : json_decode((string) $task['payload'], true, flags: JSON_THROW_ON_ERROR);
        $event    = new DataUpdateEvent($import, $payload);
        $reporter = $this->progressReporterFactory->create()->subtask($task['description']);

        try {
            $this->withMemoryLimit(fn () => $this->importers->get($task['importer'])->import($reporter, $event));

            $connection->transactional(function () use ($connection, $import, $event, $id): void {
                // in one transaction with the task being done, so the import can't look finished in between
                foreach ($event->getSpawned() as $spawned) {
                    $connection->insert(
                        'import_task',
                        [
                            'id'           => Uuid::v7(),
                            'import_id'    => $import->getId(),
                            'importer'     => $spawned['importer'],
                            'payload'      => $spawned['payload'],
                            'description'  => $spawned['description'],
                            'status'       => ImportTaskStatus::Pending->value,
                            'dependencies' => [],
                        ],
                        [
                            'id'           => 'uuid',
                            'import_id'    => 'uuid',
                            'payload'      => Types::JSON,
                            'dependencies' => Types::JSON,
                        ],
                    );
                }

                $connection->executeStatement(
                    "UPDATE import_task SET status = 'done', finished_at = :now WHERE id = :id",
                    ['now' => $this->now(), 'id' => $id],
                    ['now' => 'datetime', 'id' => 'uuid'],
                );
            });
        } catch (\Throwable $exception) {
            $connection->executeStatement(
                "UPDATE import_task SET status = 'failed', finished_at = :now, error = :error WHERE id = :id",
                ['now' => $this->now(), 'error' => $exception->getMessage(), 'id' => $id],
                ['now' => 'datetime', 'id' => 'uuid'],
            );

            $reporter->milestone(sprintf('Failed: %s', $exception->getMessage()), MilestoneType::Error);
        }

        $this->graph->dispatchReady($import);

        return $this->graph->finishIfComplete($import) ? $import : null;
    }

    /**
     * The worker running the task keeps going afterwards, so the raised limit must not outlive the task.
     */
    private function withMemoryLimit(callable $job): void
    {
        $limit = ini_set('memory_limit', self::MEMORY_LIMIT);

        try {
            $job();
        } finally {
            // PHP refuses a limit below the memory in use; the worker's own --memory-limit then stops it instead
            $previous = ini_parse_quantity((string) $limit);
            if ($previous === -1 || memory_get_usage(true) < $previous) {
                ini_set('memory_limit', (string) $limit);
            }
        }
    }

    private function now(): Carbon
    {
        return Carbon::instance($this->clock->now());
    }
}
