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
use App\Entity\ImportJobEntity;
use App\Entity\ImportTaskEntity;
use App\Event\DataUpdateEvent;
use App\Utility\MemoryLimit;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Kadet\Functional\Collections as c;
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
     * @var array<string, ProgressReporterInterface> where the tasks this process ran or runs jobs of show their
     *                                               progress, by task id, so a task and its jobs report to one place
     *                                               on the console
     */
    private array $outputs = [];

    /**
     * @var array<string, PersistingProgressReporter> reporters of those tasks, by task id
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
     */
    public function run(string $taskId): void
    {
        $id = Uuid::fromString($taskId);

        if (!$this->claimQueuedTask($id)) {
            $this->resume($id);

            return;
        }

        $task     = $this->task($id);
        $reporter = $this->taskReporter($task);
        $jobs     = [];

        try {
            $jobs = $this->runImporter($task, $reporter);
        } catch (\Throwable $exception) {
            $this->fail($id, $exception, $reporter);
        }

        if ($jobs !== []) {
            $this->graph->dispatchJobs($jobs);

            return;
        }

        $this->ended($task);
    }

    /**
     * Runs a job, unless another delivery of it already did, and tells its importer. Whatever the job throws fails
     * the job only. The job that ends the task's last one lets the importer decide how the task ends. A delivery of
     * a job that already ended resumes ending its task, in case the one that ran it didn't.
     */
    public function runJob(string $jobId): void
    {
        $id = Uuid::fromString($jobId);

        if (!$this->claimQueuedJobOfUnfinishedImport($id)) {
            $this->resumeJob($id);

            return;
        }

        $job      = $this->job($id);
        $task     = $job->getTask();
        $reporter = $this->taskReporter($task);

        $importer = $this->executeJob($job);

        $counts = $this->countJobs($task->getId());
        $this->reportJobEnded($importer, $reporter, $counts);

        if ($counts->remaining() === 0) {
            $this->endTask($task->getId()->toRfc4122());
        }
    }

    /**
     * Ends a waiting task whose jobs all ended; its importer decides how. Several jobs can end at once, all seeing
     * none left: the first to claim the task ends it. For a task that already ended, this only dispatches what
     * became ready and finishes the import, in case the call that ended it didn't.
     */
    public function endTask(string $taskId): void
    {
        $id = Uuid::fromString($taskId);

        if (!$this->claimWaitingTaskWithoutJobsLeft($id)) {
            $this->resumeEndedTask($id);

            return;
        }

        $task     = $this->task($id);
        $reporter = $this->taskReporter($task);

        try {
            /** @var SplittingDataImporter $importer */
            $importer  = $this->importers->get($task->getImporter());
            $succeeded = $importer->jobsEnded($reporter, $this->countJobs($id));

            $this->markTaskEnded($id, $succeeded ? ImportTaskStatus::Done : ImportTaskStatus::Failed);
        } catch (\Throwable $exception) {
            $this->fail($id, $exception, $reporter);
        }

        $this->ended($task);
    }

    /**
     * Runs the task's importer and ends the task, or makes it wait for the jobs the importer handed out.
     *
     * @return list<string> ids of the jobs the importer handed out, queued
     */
    private function runImporter(ImportTaskEntity $task, ProgressReporterInterface $reporter): array
    {
        $importer = $this->importers->get($task->getImporter());
        $event    = new DataUpdateEvent($task->getImport());

        MemoryLimit::raisedWhile(self::MEMORY_LIMIT, fn () => $importer->import($reporter, $event));

        if ($event->getJobs() !== [] && !$importer instanceof SplittingDataImporter) {
            throw new \LogicException(sprintf(
                'Importer %s added jobs, but only a %s can run them.',
                $importer::class,
                SplittingDataImporter::class,
            ));
        }

        return $this->em->getConnection()->transactional(
            fn (): array => $this->endOrWaitForJobs($task, $event->getJobs()),
        );
    }

    /**
     * In one transaction with queueing the jobs, so the task can't look waiting without its jobs.
     *
     * @param list<array<string, mixed>> $payloads of the jobs the importer handed out
     *
     * @return list<string> ids of the jobs queued
     */
    private function endOrWaitForJobs(ImportTaskEntity $task, array $payloads): array
    {
        if ($payloads === []) {
            $this->markTaskEnded($task->getId(), ImportTaskStatus::Done);

            return [];
        }

        // a new import may have superseded this one while the importer ran, then its jobs must not run
        if (!$this->markTaskWaitingWhileImportUnfinished($task->getId())) {
            $this->markTaskEnded($task->getId(), ImportTaskStatus::Skipped);

            return [];
        }

        $jobs = $payloads |> c\map(fn (array $payload) => new ImportJobEntity($task, $payload));

        foreach ($jobs as $job) {
            $this->em->persist($job);
        }
        $this->em->flush();

        return $jobs |> c\map(fn (ImportJobEntity $job) => $job->getId()->toRfc4122());
    }

    /**
     * For a delivery of a task another delivery claimed: dispatches the queued jobs of a waiting task again (a job
     * delivered twice runs once), and resumes after a task that ended. A running task is left to its delivery.
     */
    private function resume(Uuid $taskId): void
    {
        $task = $this->task($taskId);

        if ($task->getStatus() === ImportTaskStatus::Waiting) {
            $this->graph->dispatchJobs($this->queuedJobsOf($taskId));
        } elseif ($task->getStatus()->hasEnded()) {
            $this->ended($task);
        }
    }

    /**
     * For a delivery of a job it couldn't claim: skips the job when its import was superseded, and resumes ending its
     * task when the job ended. A running job is left to its delivery.
     */
    private function resumeJob(Uuid $jobId): void
    {
        $this->skipSuperseded($jobId);

        $job = $this->job($jobId);

        if ($job?->getStatus()->hasEnded()) {
            $this->endTask($job->getTask()->getId()->toRfc4122());
        }
    }

    /**
     * After a task ended, in case the call that ended it didn't get to what follows; nothing for one still going.
     */
    private function resumeEndedTask(Uuid $taskId): void
    {
        $task = $this->task($taskId);

        if ($task->getStatus()->hasEnded()) {
            $this->ended($task);
        }
    }

    /**
     * Runs the job and records how it ended; whatever it throws fails the job only.
     *
     * @return ?SplittingDataImporter the job's importer, null when even getting it failed
     */
    private function executeJob(ImportJobEntity $job): ?DataImporter
    {
        $reporter = $this->jobReporter($job);
        $importer = null;

        try {
            /** @var SplittingDataImporter $importer */
            $importer = $this->importers->get($job->getTask()->getImporter());
            $this->runJobOf($importer, $job->getPayload(), $job->getTask()->getImport(), $reporter);
            $this->markJobDone($job->getId());
        } catch (\Throwable $exception) {
            $this->markJobFailed($job->getId(), $exception);

            $reporter->milestone(sprintf('Job failed: %s', $exception->getMessage()), MilestoneType::Warning);
        }

        return $importer;
    }

    /**
     * @param SplittingDataImporter $importer
     */
    private function runJobOf(
        DataImporter $importer,
        array $payload,
        ImportEntity $import,
        ProgressReporterInterface $reporter,
    ): void {
        $event = new DataUpdateEvent($import);

        MemoryLimit::raisedWhile(self::MEMORY_LIMIT, fn () => $importer->runJob($reporter, $event, $payload));

        // nothing would run them, the graph only gets jobs of a running node
        if ($event->getJobs() !== []) {
            throw new \LogicException(sprintf(
                'Importer %s added jobs in a job, only its node can add them.',
                $importer::class,
            ));
        }
    }

    /**
     * @param ?SplittingDataImporter $importer
     */
    private function reportJobEnded(
        ?DataImporter $importer,
        ProgressReporterInterface $reporter,
        ImportJobCounts $counts,
    ): void {
        try {
            $importer?->jobEnded($reporter, $counts);
        } catch (\Throwable $exception) {
            // only progress, the task must still end
            $reporter->milestone(
                sprintf('Reporting progress failed: %s', $exception->getMessage()),
                MilestoneType::Warning,
            );
        }
    }

    /**
     * Skips a queued job of a superseded import, and its task once no job of it is left; a job already claimed by
     * another delivery stays as it is.
     */
    private function skipSuperseded(Uuid $jobId): void
    {
        $now = $this->now();

        if (!$this->skipQueuedJob($jobId, $now)) {
            return;
        }

        $taskId = $this->job($jobId)->getTask()->getId();

        $this->skipWaitingTaskWithoutJobsLeft($taskId, $now);
        $this->forgetReporters($taskId);
    }

    /**
     * After the task ended: dispatches the tasks that became ready and finishes the import when it was the last.
     */
    private function ended(ImportTaskEntity $task): void
    {
        $this->forgetReporters($task->getId());

        $this->graph->dispatchReady($task->getImport());
        $this->graph->finishIfComplete($task->getImport());
    }

    private function fail(Uuid $taskId, \Throwable $exception, ProgressReporterInterface $reporter): void
    {
        $this
            ->query(
                'UPDATE App\Entity\ImportTaskEntity t SET t.status = :failed, t.finishedAt = :now, t.error = :error
                 WHERE t.id = :id',
                [
                    'failed' => ImportTaskStatus::Failed,
                    'now'    => $this->now(),
                    'error'  => $exception->getMessage(),
                    'id'     => $taskId,
                ],
            )
            ->execute();

        $reporter->milestone(sprintf('Failed: %s', $exception->getMessage()), MilestoneType::Error);
    }

    private function claimQueuedTask(Uuid $taskId): bool
    {
        $changed = $this
            ->query(
                'UPDATE App\Entity\ImportTaskEntity t SET t.status = :running, t.startedAt = :now
                 WHERE t.id = :id AND t.status = :queued',
                [
                    'running' => ImportTaskStatus::Running,
                    'now'     => $this->now(),
                    'id'      => $taskId,
                    'queued'  => ImportTaskStatus::Queued,
                ],
            )
            ->execute();

        return $changed === 1;
    }

    /**
     * Jobs a node queued just after a new import superseded it don't run.
     */
    private function claimQueuedJobOfUnfinishedImport(Uuid $jobId): bool
    {
        $changed = $this
            ->query(
                'UPDATE App\Entity\ImportJobEntity j SET j.status = :running, j.startedAt = :now
                 WHERE j.id = :id AND j.status = :queued AND j.task IN (
                     SELECT t.id FROM App\Entity\ImportTaskEntity t JOIN t.import i WHERE i.status = :unfinished
                 )',
                [
                    'running'    => ImportJobStatus::Running,
                    'now'        => $this->now(),
                    'id'         => $jobId,
                    'queued'     => ImportJobStatus::Queued,
                    'unfinished' => ImportStatus::Running,
                ],
            )
            ->execute();

        return $changed === 1;
    }

    private function claimWaitingTaskWithoutJobsLeft(Uuid $taskId): bool
    {
        $changed = $this
            ->query(
                'UPDATE App\Entity\ImportTaskEntity t SET t.status = :running
                 WHERE t.id = :id AND t.status = :waiting
                   AND NOT EXISTS (
                       SELECT j.id FROM App\Entity\ImportJobEntity j WHERE j.task = t AND j.status IN (:unended)
                   )',
                [
                    'running' => ImportTaskStatus::Running,
                    'id'      => $taskId,
                    'waiting' => ImportTaskStatus::Waiting,
                    'unended' => ImportJobStatus::unended(),
                ],
            )
            ->execute();

        return $changed === 1;
    }

    private function skipWaitingTaskWithoutJobsLeft(Uuid $taskId, Carbon $now): void
    {
        $this
            ->query(
                'UPDATE App\Entity\ImportTaskEntity t SET t.status = :skipped, t.finishedAt = :now
                 WHERE t.id = :id AND t.status = :waiting
                   AND NOT EXISTS (
                       SELECT j.id FROM App\Entity\ImportJobEntity j WHERE j.task = t AND j.status IN (:unended)
                   )',
                [
                    'skipped' => ImportTaskStatus::Skipped,
                    'now'     => $now,
                    'id'      => $taskId,
                    'waiting' => ImportTaskStatus::Waiting,
                    'unended' => ImportJobStatus::unended(),
                ],
            )
            ->execute();
    }

    private function markTaskWaitingWhileImportUnfinished(Uuid $taskId): bool
    {
        $changed = $this
            ->query(
                'UPDATE App\Entity\ImportTaskEntity t SET t.status = :waiting
                 WHERE t.id = :id
                   AND t.import IN (SELECT i.id FROM App\Entity\ImportEntity i WHERE i.status = :unfinished)',
                ['waiting' => ImportTaskStatus::Waiting, 'id' => $taskId, 'unfinished' => ImportStatus::Running],
            )
            ->execute();

        return $changed === 1;
    }

    private function markTaskEnded(Uuid $taskId, ImportTaskStatus $status): void
    {
        $this
            ->query(
                'UPDATE App\Entity\ImportTaskEntity t SET t.status = :status, t.finishedAt = :now WHERE t.id = :id',
                ['status' => $status, 'now' => $this->now(), 'id' => $taskId],
            )
            ->execute();
    }

    private function markJobDone(Uuid $jobId): void
    {
        $this
            ->query(
                'UPDATE App\Entity\ImportJobEntity j SET j.status = :done, j.finishedAt = :now WHERE j.id = :id',
                ['done' => ImportJobStatus::Done, 'now' => $this->now(), 'id' => $jobId],
            )
            ->execute();
    }

    private function markJobFailed(Uuid $jobId, \Throwable $exception): void
    {
        $this
            ->query(
                'UPDATE App\Entity\ImportJobEntity j SET j.status = :failed, j.finishedAt = :now, j.error = :error
                 WHERE j.id = :id',
                [
                    'failed' => ImportJobStatus::Failed,
                    'now'    => $this->now(),
                    'error'  => $exception->getMessage(),
                    'id'     => $jobId,
                ],
            )
            ->execute();
    }

    private function skipQueuedJob(Uuid $jobId, Carbon $now): bool
    {
        $changed = $this
            ->query(
                'UPDATE App\Entity\ImportJobEntity j SET j.status = :skipped, j.finishedAt = :now
                 WHERE j.id = :id AND j.status = :queued',
                [
                    'skipped' => ImportJobStatus::Skipped,
                    'now'     => $now,
                    'id'      => $jobId,
                    'queued'  => ImportJobStatus::Queued,
                ],
            )
            ->execute();

        return $changed === 1;
    }

    /**
     * The task as it is now: other workers change it with UPDATE statements.
     */
    private function task(Uuid $taskId): ImportTaskEntity
    {
        return $this
            ->query('SELECT t FROM App\Entity\ImportTaskEntity t WHERE t.id = :id', ['id' => $taskId])
            ->setHint(Query::HINT_REFRESH, true)
            ->getSingleResult();
    }

    /**
     * The job, with its task, as they are now: other workers change them with UPDATE statements.
     */
    private function job(Uuid $jobId): ?ImportJobEntity
    {
        return $this
            ->query('SELECT j, t FROM App\Entity\ImportJobEntity j JOIN j.task t WHERE j.id = :id', ['id' => $jobId])
            ->setHint(Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
    }

    /**
     * @return list<string>
     */
    private function queuedJobsOf(Uuid $taskId): array
    {
        $jobs = $this
            ->query(
                'SELECT j FROM App\Entity\ImportJobEntity j WHERE j.task = :task AND j.status = :queued ORDER BY j.id',
                ['task' => $taskId, 'queued' => ImportJobStatus::Queued],
            )
            ->getResult();

        return $jobs |> c\map(fn (ImportJobEntity $job) => $job->getId()->toRfc4122());
    }

    private function countJobs(Uuid $taskId): ImportJobCounts
    {
        $rows = $this
            ->query(
                'SELECT j.status, COUNT(j.id) AS jobs FROM App\Entity\ImportJobEntity j
                 WHERE j.task = :task GROUP BY j.status',
                ['task' => $taskId],
            )
            ->getArrayResult();

        $jobsByStatus = $rows
            |> c\keyBy(fn (array $row) => $row['status']->value)
            |> c\map(fn (array $row) => (int) $row['jobs'])
            |> c\toArray();

        return ImportJobCounts::fromStatuses($jobsByStatus);
    }

    /**
     * Stores the progress of the task, kept while the task runs or waits, so it stores at most once a second.
     */
    private function taskReporter(ImportTaskEntity $task): ProgressReporterInterface
    {
        return $this->reporters[$task->getId()->toRfc4122()] ??= PersistingProgressReporter::ofTask(
            $task->getId(),
            $this->outputOf($task),
            $this->em,
            $this->clock,
        );
    }

    /**
     * Stores the progress of the job, shown where its task's is.
     */
    private function jobReporter(ImportJobEntity $job): ProgressReporterInterface
    {
        return PersistingProgressReporter::ofJob(
            $job->getId(),
            $this->outputOf($job->getTask()),
            $this->em,
            $this->clock,
        );
    }

    private function outputOf(ImportTaskEntity $task): ProgressReporterInterface
    {
        return $this->outputs[$task->getId()->toRfc4122()] ??= $this->progressReporterFactory
            ->create()
            ->subtask($task->getDescription());
    }

    private function forgetReporters(Uuid $taskId): void
    {
        unset($this->outputs[$taskId->toRfc4122()], $this->reporters[$taskId->toRfc4122()]);
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
