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

use App\Entity\ImportTaskEntity;
use App\Event\DataUpdateEvent;
use App\Utility\MemoryLimit;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * Runs one task of the import graph, its importer; then the tasks that became ready.
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
     * its dependents are skipped; it isn't retried, the next import plans it again. A delivery of a task that already
     * ran resumes what the one that ran it may not have finished: the dispatch of its dependents.
     */
    public function run(string $taskId): void
    {
        $id = Uuid::fromString($taskId);

        if (!$this->claimQueuedTask($id)) {
            $this->resumeEndedTask($id);

            return;
        }

        $task     = $this->task($id);
        $reporter = $this->taskReporter($task);

        try {
            $this->runImporter($task, $reporter);
            $this->markTaskDone($id);
        } catch (\Throwable $exception) {
            $this->fail($id, $exception, $reporter);
        }

        $this->ended($task);
    }

    private function runImporter(ImportTaskEntity $task, ProgressReporterInterface $reporter): void
    {
        $importer = $this->importers->get($task->getImporter());
        $event    = new DataUpdateEvent($task->getImport());

        MemoryLimit::raisedWhile(self::MEMORY_LIMIT, fn () => $importer->import($reporter, $event));
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
     * After the task ended: dispatches the tasks that became ready and finishes the import when it was the last.
     */
    private function ended(ImportTaskEntity $task): void
    {
        $this->graph->dispatchReady($task->getImport());
        $this->graph->finishIfComplete($task->getImport());
    }

    private function fail(Uuid $taskId, \Throwable $exception, ProgressReporterInterface $reporter): void
    {
        $this
            ->query(
                'UPDATE App\Entity\ImportTaskEntity t SET t.status = :failed, t.finishedAt = :now, t.error = :error WHERE t.id = :id',
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
                'UPDATE App\Entity\ImportTaskEntity t SET t.status = :running, t.startedAt = :now WHERE t.id = :id AND t.status = :queued',
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

    private function markTaskDone(Uuid $taskId): void
    {
        $this
            ->query(
                'UPDATE App\Entity\ImportTaskEntity t SET t.status = :done, t.finishedAt = :now WHERE t.id = :id',
                [
                    'done' => ImportTaskStatus::Done,
                    'now'  => $this->now(),
                    'id'   => $taskId,
                ],
            )
            ->execute();
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
     * Shows the progress of the task and stores it, see PersistingProgressReporter.
     */
    private function taskReporter(ImportTaskEntity $task): ProgressReporterInterface
    {
        return PersistingProgressReporter::ofTask(
            $task->getId(),
            $this->progressReporterFactory->create()->subtask($task->getDescription()),
            $this->em,
            $this->clock,
        );
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
