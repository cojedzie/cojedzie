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
 * Runs one task of the import graph, its importer, and the jobs the importer split its work into; then the tasks that
 * became ready.
 */
class ImportTaskRunner
{
    /**
     * Measured on 2026-10-28 (a full day of ZTM data, in one task): 48 MB peak without debug, 906 MB with it, as
     * Doctrine keeps every query for the profiler. The import runs in debug through `task api:update`.
     */
    private const string MEMORY_LIMIT = '2G';

    /**
     * @var array<string, ProgressReporterInterface> reporters of the tasks this process ran or runs jobs of, by task
     *                                               id, so a task and its jobs report to one place on the console
     */
    private array $reporters = [];

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
     * its dependents are skipped; it isn't retried, the next import plans it again. When the importer hands out jobs,
     * the task waits for them instead, see runJob(). A delivery of a task that already ran resumes what the one that
     * ran it may not have finished: the dispatch of its jobs, or of its dependents.
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
            return $this->resume($id);
        }

        $task = $connection->fetchAssociative(
            'SELECT import_id, importer, description FROM import_task WHERE id = :id',
            ['id' => $id],
            ['id' => 'uuid'],
        );

        $import   = $this->em->find(ImportEntity::class, Uuid::fromString($task['import_id']));
        $event    = new DataUpdateEvent($import);
        $reporter = $this->reporter($id, $task['description']);
        $jobs     = [];

        try {
            $importer = $this->importers->get($task['importer']);
            $this->withMemoryLimit(fn () => $importer->import($reporter, $event));

            if ($event->getJobs() !== [] && !$importer instanceof SplittingDataImporter) {
                throw new \LogicException(sprintf(
                    'Importer %s added jobs, but only a %s can run them.',
                    $importer::class,
                    SplittingDataImporter::class,
                ));
            }

            $jobs = $connection->transactional(function () use ($connection, $event, $id): array {
                if ($event->getJobs() === []) {
                    $connection->executeStatement(
                        "UPDATE import_task SET status = 'done', finished_at = :now WHERE id = :id",
                        ['now' => $this->now(), 'id' => $id],
                        ['now' => 'datetime', 'id' => 'uuid'],
                    );

                    return [];
                }

                // a new import may have superseded this one while the importer ran, then its jobs must not run
                $waiting = $connection->executeStatement(
                    "UPDATE import_task SET status = 'waiting'
                     WHERE id = :id AND import_id IN (SELECT id FROM import WHERE finished_at IS NULL)",
                    ['id' => $id],
                    ['id' => 'uuid'],
                );

                if ($waiting !== 1) {
                    $connection->executeStatement(
                        "UPDATE import_task SET status = 'skipped', finished_at = :now WHERE id = :id",
                        ['now' => $this->now(), 'id' => $id],
                        ['now' => 'datetime', 'id' => 'uuid'],
                    );

                    return [];
                }

                $jobs = [];

                // in one transaction with the task waiting, so it can't look waiting without its jobs
                foreach ($event->getJobs() as $payload) {
                    $jobs[] = $job = Uuid::v7();

                    $connection->insert(
                        'import_job',
                        [
                            'id'      => $job,
                            'task_id' => $id,
                            'payload' => $payload,
                            'status'  => ImportJobStatus::Queued->value,
                        ],
                        [
                            'id'      => 'uuid',
                            'task_id' => 'uuid',
                            'payload' => Types::JSON,
                        ],
                    );
                }

                return $jobs;
            });
        } catch (\Throwable $exception) {
            $this->fail($id, $exception, $reporter);
        }

        if ($jobs !== []) {
            $this->graph->dispatchJobs(array_map(fn (Uuid $job) => $job->toRfc4122(), $jobs));

            return null;
        }

        return $this->ended($id, $import);
    }

    /**
     * Runs a job, unless another delivery of it already did, and tells its importer. Whatever the job throws fails
     * the job only. The job that ends the task's last one lets the importer decide how the task ends. A delivery of
     * a job that already ended resumes ending its task, in case the one that ran it didn't.
     *
     * @return ?ImportEntity the import, when this job finished it
     */
    public function runJob(string $jobId): ?ImportEntity
    {
        $connection = $this->em->getConnection();
        $id         = Uuid::fromString($jobId);

        // only while the import is unfinished: jobs a node queued just after a new import superseded it don't run
        $claimed = $connection->executeStatement(
            "UPDATE import_job SET status = 'running', started_at = :now
             WHERE id = :id AND status = 'queued' AND task_id IN (
                 SELECT t.id FROM import_task t JOIN import i ON i.id = t.import_id WHERE i.finished_at IS NULL
             )",
            ['now' => $this->now(), 'id' => $id],
            ['now' => 'datetime', 'id' => 'uuid'],
        );

        if ($claimed !== 1) {
            $this->skipSuperseded($id);

            $job = $connection->fetchAssociative('SELECT task_id, status FROM import_job WHERE id = :id', ['id' => $id], ['id' => 'uuid']);

            $ended = [ImportJobStatus::Done->value, ImportJobStatus::Failed->value, ImportJobStatus::Skipped->value];

            return $job !== false && in_array($job['status'], $ended, true)
                ? $this->endTask(Uuid::fromString($job['task_id'])->toRfc4122())
                : null;
        }

        $job = $connection->fetchAssociative(
            'SELECT j.task_id, j.payload, t.import_id, t.importer, t.description
             FROM import_job j JOIN import_task t ON t.id = j.task_id WHERE j.id = :id',
            ['id' => $id],
            ['id' => 'uuid'],
        );

        $taskId   = Uuid::fromString($job['task_id']);
        $import   = $this->em->find(ImportEntity::class, Uuid::fromString($job['import_id']));
        $reporter = $this->reporter($taskId, $job['description']);
        $importer = null;

        try {
            /** @var SplittingDataImporter $importer */
            $importer = $this->importers->get($job['importer']);
            $payload  = json_decode((string) $job['payload'], true, flags: JSON_THROW_ON_ERROR);
            $event    = new DataUpdateEvent($import);
            $this->withMemoryLimit(fn () => $importer->runJob($reporter, $event, $payload));

            // nothing would run them, the graph only gets jobs of a running node
            if ($event->getJobs() !== []) {
                throw new \LogicException(sprintf('Importer %s added jobs in a job, only its node can add them.', $importer::class));
            }

            $connection->executeStatement(
                "UPDATE import_job SET status = 'done', finished_at = :now WHERE id = :id",
                ['now' => $this->now(), 'id' => $id],
                ['now' => 'datetime', 'id' => 'uuid'],
            );
        } catch (\Throwable $exception) {
            $connection->executeStatement(
                "UPDATE import_job SET status = 'failed', finished_at = :now, error = :error WHERE id = :id",
                ['now' => $this->now(), 'error' => $exception->getMessage(), 'id' => $id],
                ['now' => 'datetime', 'id' => 'uuid'],
            );

            $reporter->milestone(sprintf('Job failed: %s', $exception->getMessage()), MilestoneType::Warning);
        }

        $counts = $this->countJobs($taskId);

        try {
            $importer?->jobEnded($reporter, $counts);
        } catch (\Throwable $exception) {
            // only progress, the task must still end
            $reporter->milestone(sprintf('Reporting progress failed: %s', $exception->getMessage()), MilestoneType::Warning);
        }

        if ($counts->remaining() > 0) {
            return null;
        }

        return $this->endTask($taskId->toRfc4122());
    }

    /**
     * Ends a waiting task whose jobs all ended; its importer decides how. Several jobs can end at once, all seeing
     * none left: the first to claim the task ends it. For a task that already ended, this only dispatches what
     * became ready and finishes the import, in case the call that ended it didn't.
     *
     * @return ?ImportEntity the import, when this call finished it
     */
    public function endTask(string $task): ?ImportEntity
    {
        $connection = $this->em->getConnection();
        $taskId     = Uuid::fromString($task);

        $claimed = $connection->executeStatement(
            "UPDATE import_task SET status = 'running' WHERE id = :task AND status = 'waiting' AND NOT EXISTS (SELECT 1 FROM import_job WHERE task_id = :task AND status IN ('queued', 'running'))",
            ['task' => $taskId],
            ['task' => 'uuid'],
        );

        if ($claimed !== 1) {
            return $this->hasEnded($taskId) ? $this->ended($taskId, $this->importOf($taskId)) : null;
        }

        $row = $connection->fetchAssociative(
            'SELECT import_id, importer, description FROM import_task WHERE id = :id',
            ['id' => $taskId],
            ['id' => 'uuid'],
        );

        $import   = $this->em->find(ImportEntity::class, Uuid::fromString($row['import_id']));
        $reporter = $this->reporter($taskId, $row['description']);

        try {
            /** @var SplittingDataImporter $importer */
            $importer  = $this->importers->get($row['importer']);
            $succeeded = $importer->jobsEnded($reporter, $this->countJobs($taskId));

            $connection->executeStatement(
                'UPDATE import_task SET status = :status, finished_at = :now WHERE id = :id',
                [
                    'status' => ($succeeded ? ImportTaskStatus::Done : ImportTaskStatus::Failed)->value,
                    'now'    => $this->now(),
                    'id'     => $taskId,
                ],
                ['now' => 'datetime', 'id' => 'uuid'],
            );
        } catch (\Throwable $exception) {
            $this->fail($taskId, $exception, $reporter);
        }

        return $this->ended($taskId, $import);
    }

    /**
     * For a delivery of a task another delivery claimed: dispatches the queued jobs of a waiting task again (a job
     * delivered twice runs once), and resumes after a task that ended. A running task is left to its delivery.
     */
    private function resume(Uuid $taskId): ?ImportEntity
    {
        if ($this->status($taskId) === ImportTaskStatus::Waiting) {
            $jobs = $this->em->getConnection()->fetchFirstColumn(
                "SELECT id FROM import_job WHERE task_id = :task AND status = 'queued' ORDER BY id",
                ['task' => $taskId],
                ['task' => 'uuid'],
            );

            $this->graph->dispatchJobs(array_map(fn (string $job) => Uuid::fromString($job)->toRfc4122(), $jobs));

            return null;
        }

        return $this->hasEnded($taskId) ? $this->ended($taskId, $this->importOf($taskId)) : null;
    }

    private function status(Uuid $taskId): ImportTaskStatus
    {
        return ImportTaskStatus::from($this->em->getConnection()->fetchOne(
            'SELECT status FROM import_task WHERE id = :id',
            ['id' => $taskId],
            ['id' => 'uuid'],
        ));
    }

    private function hasEnded(Uuid $taskId): bool
    {
        return in_array($this->status($taskId), [ImportTaskStatus::Done, ImportTaskStatus::Failed, ImportTaskStatus::Skipped], true);
    }

    private function importOf(Uuid $taskId): ImportEntity
    {
        $importId = $this->em->getConnection()->fetchOne('SELECT import_id FROM import_task WHERE id = :id', ['id' => $taskId], ['id' => 'uuid']);

        return $this->em->find(ImportEntity::class, Uuid::fromString($importId));
    }

    /**
     * Skips a queued job of a superseded import, and its task once no job of it is left; a job already claimed by
     * another delivery stays as it is.
     */
    private function skipSuperseded(Uuid $jobId): void
    {
        $connection = $this->em->getConnection();
        $now        = $this->now();

        $skipped = $connection->executeStatement(
            "UPDATE import_job SET status = 'skipped', finished_at = :now WHERE id = :id AND status = 'queued'",
            ['now' => $now, 'id' => $jobId],
            ['now' => 'datetime', 'id' => 'uuid'],
        );

        if ($skipped !== 1) {
            return;
        }

        $taskId = Uuid::fromString($connection->fetchOne('SELECT task_id FROM import_job WHERE id = :id', ['id' => $jobId], ['id' => 'uuid']));

        $connection->executeStatement(
            "UPDATE import_task SET status = 'skipped', finished_at = :now WHERE id = :task AND status = 'waiting' AND NOT EXISTS (SELECT 1 FROM import_job WHERE task_id = :task AND status IN ('queued', 'running'))",
            ['now' => $now, 'task' => $taskId],
            ['now' => 'datetime', 'task' => 'uuid'],
        );

        unset($this->reporters[$taskId->toRfc4122()]);
    }

    /**
     * After the task ended: dispatches the tasks that became ready and finishes the import when it was the last.
     */
    private function ended(Uuid $taskId, ImportEntity $import): ?ImportEntity
    {
        unset($this->reporters[$taskId->toRfc4122()]);

        $this->graph->dispatchReady($import);

        return $this->graph->finishIfComplete($import) ? $import : null;
    }

    private function fail(Uuid $taskId, \Throwable $exception, ProgressReporterInterface $reporter): void
    {
        $this->em->getConnection()->executeStatement(
            "UPDATE import_task SET status = 'failed', finished_at = :now, error = :error WHERE id = :id",
            ['now' => $this->now(), 'error' => $exception->getMessage(), 'id' => $taskId],
            ['now' => 'datetime', 'id' => 'uuid'],
        );

        $reporter->milestone(sprintf('Failed: %s', $exception->getMessage()), MilestoneType::Error);
    }

    private function countJobs(Uuid $taskId): ImportJobCounts
    {
        return ImportJobCounts::fromStatuses(array_map(intval(...), $this->em->getConnection()->fetchAllKeyValue(
            'SELECT status, COUNT(*) FROM import_job WHERE task_id = :task GROUP BY status',
            ['task' => $taskId],
            ['task' => 'uuid'],
        )));
    }

    private function reporter(Uuid $taskId, string $description): ProgressReporterInterface
    {
        return $this->reporters[$taskId->toRfc4122()] ??= $this->progressReporterFactory->create()->subtask($description);
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
