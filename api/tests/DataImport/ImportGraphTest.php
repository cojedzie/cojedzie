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

namespace App\Tests\DataImport;

use App\DataImport\DataImporter;
use App\DataImport\ImportGraph;
use App\DataImport\ImportJobCounts;
use App\DataImport\ImportTaskRunner;
use App\DataImport\ImportTaskStatus;
use App\DataImport\ProgressReporterFactory;
use App\DataImport\ProgressReporterInterface;
use App\DataImport\SplittingDataImporter;
use App\Entity\ImportEntity;
use App\Event\DataUpdateEvent;
use App\Message\RunImportJob;
use App\Message\RunImportTask;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\Exception as PdoDriverException;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Clock\ClockInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

/**
 * The import is a graph of tasks, one per importer, run by workers as RunImportTask messages. An importer may split
 * its work into jobs, run as RunImportJob messages.
 */
final class ImportGraphTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private Connection $connection;
    private ImportTaskRunner $runner;

    /**
     * @var \ArrayObject<int, array{string, DataUpdateEvent}>
     */
    private \ArrayObject $log;
    private RecordingBus $bus;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();

        $this->em         = self::getContainer()->get(EntityManagerInterface::class);
        $this->connection = $this->em->getConnection();
        $this->connection->beginTransaction();

        $this->log = new \ArrayObject();
        $this->bus = new RecordingBus();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->connection->rollBack();

        parent::tearDown();
    }

    public function testImportersRunAfterTheirDependenciesAndTheImportIsFinished(): void
    {
        $lines = new LinesImporter('lines', [], $this->log);
        $stops = new StopsImporter('stops', [LinesImporter::class], $this->log);

        $import = $this->graph([$stops, $lines])->start();
        $this->work();

        $this->assertSame(['lines', 'stops'], $this->order());
        $this->assertSame($import->getId()->toRfc4122(), $this->log[0][1]->import->getId()->toRfc4122());
        $this->assertSame('2026-10-28 12:00:00', $this->import($import)['started_at']);
        $this->assertSame('2026-10-28 12:00:00', $this->import($import)['finished_at']);
        $this->assertTrue((bool) $this->import($import)['succeeded']);
    }

    public function testImportersDependingOnSeveralReadyOnesRun(): void
    {
        $provider = new ProviderImporter('provider', [], $this->log);
        $stops    = new StopsImporter('stops', [ProviderImporter::class], $this->log);
        $lines    = new LinesImporter('lines', [ProviderImporter::class], $this->log);
        $tracks   = new TracksImporter('tracks', [StopsImporter::class, LinesImporter::class], $this->log);

        $this->graph([$tracks, $lines, $stops, $provider])->start();
        $this->work();

        $order = $this->order();
        $this->assertSame('provider', $order[0]);
        $this->assertSame('tracks', $order[3]);
        $this->assertEqualsCanonicalizing(['stops', 'lines'], [$order[1], $order[2]]);
    }

    public function testUnresolvableDependenciesFail(): void
    {
        $lines = new LinesImporter('lines', [], $this->log);
        $stops = new StopsImporter('stops', ['App\\NoSuchImporter'], $this->log);

        try {
            $this->graph([$lines, $stops])->start();
            $this->fail('The import should have failed.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString(StopsImporter::class, $exception->getMessage());
        }

        // the graph is resolved before anything is written
        $this->assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM import'));
        $this->assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM import_task'));
        $this->assertSame([], $this->bus->messages);
    }

    public function testCircularDependenciesFail(): void
    {
        $lines = new LinesImporter('lines', [StopsImporter::class], $this->log);
        $stops = new StopsImporter('stops', [LinesImporter::class], $this->log);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/LinesImporter.*StopsImporter|StopsImporter.*LinesImporter/');

        $this->graph([$lines, $stops])->start();
    }

    public function testMemoryLimitIsRaisedForATaskAndRestored(): void
    {
        $limit = ini_get('memory_limit');

        $probe = new class('probe', [], $this->log) extends RecordingImporter {
            public ?string $limit = null;

            #[\Override]
            public function import(ProgressReporterInterface $reporter, DataUpdateEvent $event): void
            {
                $this->limit = ini_get('memory_limit');
            }
        };

        try {
            $this->graph([$probe])->start();
            $this->work();

            $this->assertSame('2G', $probe->limit);
            $this->assertSame($limit, ini_get('memory_limit'));
        } finally {
            ini_set('memory_limit', $limit);
        }
    }

    public function testMemoryInUseAboveThePreviousLimitIsNotAnError(): void
    {
        $limit = ini_get('memory_limit');
        ini_set('memory_limit', (string) (memory_get_usage(true) + (32 << 20)));

        // keeps 64 MB in use after the task, more than the limit before it allowed
        $hoarder = new class('hoarder', [], $this->log) extends RecordingImporter {
            public string $data = '';

            #[\Override]
            public function import(ProgressReporterInterface $reporter, DataUpdateEvent $event): void
            {
                $this->data = str_repeat('x', 64 << 20);
            }
        };

        $errors = [];
        set_error_handler(function (int $level, string $message) use (&$errors) {
            $errors[] = $message;

            return true;
        });

        try {
            $this->graph([$hoarder])->start();
            $this->work();

            $this->assertSame([], $errors);
        } finally {
            restore_error_handler();
            $hoarder->data = '';
            ini_set('memory_limit', $limit);
        }
    }

    public function testOnlyTasksWithoutDependenciesAreDispatchedAtStart(): void
    {
        $provider = new ProviderImporter('provider', [], $this->log);
        $lines    = new LinesImporter('lines', [], $this->log);
        $stops    = new StopsImporter('stops', [ProviderImporter::class], $this->log);

        $import = $this->graph([$provider, $lines, $stops])->start();

        $this->assertCount(2, $this->bus->messages);
        $this->assertSame(
            ['lines' => ImportTaskStatus::Queued, 'provider' => ImportTaskStatus::Queued, 'stops' => ImportTaskStatus::Pending],
            $this->statuses($import),
        );
        $this->assertSame([], $this->order());
    }

    public function testDependentIsDispatchedOnceWhenTwoDependenciesFinishTogether(): void
    {
        $provider = new ProviderImporter('provider', [], $this->log);
        $lines    = new LinesImporter('lines', [], $this->log);
        $tracks   = new TracksImporter('tracks', [ProviderImporter::class, LinesImporter::class], $this->log);

        $graph  = $this->graph([$provider, $lines, $tracks]);
        $import = $graph->start();

        // both workers mark their task done, then both look for ready tasks
        $this->connection->executeStatement(
            "UPDATE import_task SET status = 'done' WHERE description IN ('provider', 'lines')",
        );
        $this->bus->take();

        $graph->dispatchReady($import);
        $graph->dispatchReady($import);

        $dispatched = $this->bus->take();
        $this->assertCount(1, $dispatched);
        $this->assertSame($this->taskId($import, 'tracks'), $dispatched[0]->taskId);
    }

    public function testDuplicateDeliveryDoesNothing(): void
    {
        $lines = new LinesImporter('lines', [], $this->log);

        $this->graph([$lines])->start();

        $this->runner->run($this->bus->messages[0]->taskId);
        $this->runner->run($this->bus->messages[0]->taskId);

        $this->assertSame(['lines'], $this->order());
    }

    public function testFailedTaskSkipsItsDependents(): void
    {
        $provider = new ProviderImporter('provider', [], $this->log);
        $lines    = new FailingImporter('lines', [ProviderImporter::class], $this->log);
        $tracks   = new TracksImporter('tracks', [FailingImporter::class], $this->log);
        $stops    = new StopsImporter('stops', [ProviderImporter::class], $this->log);

        $import = $this->graph([$provider, $lines, $tracks, $stops])->start();
        $this->work();

        $this->assertSame(['provider', 'stops'], array_values(array_diff($this->order(), ['lines'])));
        $this->assertSame(
            [
                'lines'    => ImportTaskStatus::Failed,
                'provider' => ImportTaskStatus::Done,
                'stops'    => ImportTaskStatus::Done,
                'tracks'   => ImportTaskStatus::Skipped,
            ],
            $this->statuses($import),
        );
        $this->assertSame(
            'Feed of lines is unavailable',
            $this->connection->fetchOne("SELECT error FROM import_task WHERE description = 'lines'"),
        );
    }

    public function testImportIsNotSucceededWhenACoreTaskFailed(): void
    {
        $provider = new ProviderImporter('provider', [], $this->log);
        $lines    = new FailingImporter('lines', [ProviderImporter::class], $this->log);

        $import = $this->graph([$provider, $lines])->start();
        $this->work();

        $this->assertNotNull($this->import($import)['finished_at']);
        $this->assertFalse((bool) $this->import($import)['succeeded']);
    }

    public function testStartSupersedesAnUnfinishedImport(): void
    {
        $lines = new LinesImporter('lines', [], $this->log);
        $stops = new StopsImporter('stops', [LinesImporter::class], $this->log);
        $graph = $this->graph([$lines, $stops]);

        $previous = $graph->start();
        $current  = $graph->start();

        $this->assertSame(
            ['lines' => ImportTaskStatus::Skipped, 'stops' => ImportTaskStatus::Skipped],
            $this->statuses($previous),
        );
        $this->assertSame('2026-10-28 12:00:00', $this->import($previous)['finished_at']);
        $this->assertFalse((bool) $this->import($previous)['succeeded']);

        // the message of the superseded task is still delivered, but does nothing
        $this->work();

        $this->assertSame(['lines', 'stops'], $this->order());
        $this->assertSame($current->getId()->toRfc4122(), $this->log[0][1]->import->getId()->toRfc4122());
    }

    public function testStartIsRetriedWhenItsTransactionDeadlocks(): void
    {
        $lines = new LinesImporter('lines', [], $this->log);

        // superseding can deadlock with a worker claiming a job of the superseded import
        $import = $this->graph([$lines], new DeadlockingClock(deadlocks: 2))->start();

        $this->assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM import'));
        $this->assertSame(['lines' => ImportTaskStatus::Queued], $this->statuses($import));
        $this->assertCount(1, $this->bus->messages);
    }

    public function testStartGivesUpAfterThreeDeadlocks(): void
    {
        $lines = new LinesImporter('lines', [], $this->log);

        try {
            $this->graph([$lines], new DeadlockingClock(deadlocks: 3))->start();
            $this->fail('The deadlock was not rethrown.');
        } catch (DeadlockException) {
        }

        $this->assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM import'));
        $this->assertSame([], $this->bus->messages);
    }

    public function testNodeWithJobsWaitsAndItsDependentsWaitForIt(): void
    {
        $schedule = new SplittingImporter('schedule', [], $this->log, [['part' => 1], ['part' => 2]]);
        $tracks   = new TracksImporter('tracks', [SplittingImporter::class], $this->log);

        $import = $this->graph([$schedule, $tracks])->start();

        $this->workOne();
        $this->assertSame(['schedule' => ImportTaskStatus::Waiting, 'tracks' => ImportTaskStatus::Pending], $this->statuses($import));
        $this->assertSame([['part' => 1], ['part' => 2]], array_map($this->jobPayload(...), $this->bus->messages));

        $this->workOne();
        $this->assertSame(['schedule' => ImportTaskStatus::Waiting, 'tracks' => ImportTaskStatus::Pending], $this->statuses($import));
        $this->assertNull($this->import($import)['finished_at']);

        $this->workOne();
        $this->assertSame(['schedule' => ImportTaskStatus::Done, 'tracks' => ImportTaskStatus::Queued], $this->statuses($import));
        $this->assertSame([[2, 2, 0]], $schedule->finished);

        $this->work();
        $this->assertSame(['schedule', 'tracks'], $this->order());
        $this->assertNotNull($this->import($import)['finished_at']);
        $this->assertTrue((bool) $this->import($import)['succeeded']);
    }

    /**
     * @param list<int> $order of the jobs ending
     */
    #[DataProvider('jobOrders')]
    public function testJobEndingWhileAnotherRunsDoesNotEndTheNode(array $order): void
    {
        $schedule = new SplittingImporter('schedule', [], $this->log, [['part' => 1], ['part' => 2]]);
        $tracks   = new TracksImporter('tracks', [SplittingImporter::class], $this->log);

        $import = $this->graph([$schedule, $tracks])->start();
        $this->workOne();
        $jobs = $this->bus->take();

        // the first job ends while the second one runs on another worker
        $schedule->onJob = function (array $payload) use ($jobs, $order, $schedule): void {
            if ($payload['part'] === $order[0] + 1) {
                $schedule->onJob = null;
                $this->runner->runJob($jobs[$order[1]]->jobId);
            }
        };

        $this->runner->runJob($jobs[$order[0]]->jobId);
        $this->runner->runJob($jobs[$order[1]]->jobId);
        $this->runner->runJob($jobs[$order[0]]->jobId);

        $this->assertSame([[2, 2, 0]], $schedule->finished);
        $dispatched = $this->bus->take();
        $this->assertCount(1, $dispatched);
        $this->assertSame($this->taskId($import, 'tracks'), $dispatched[0]->taskId);
    }

    public static function jobOrders(): iterable
    {
        yield 'first, then second' => [[0, 1]];
        yield 'second, then first' => [[1, 0]];
    }

    public function testNodeEndsOnceWhenItsLastJobsEndTogether(): void
    {
        $schedule = new SplittingImporter('schedule', [], $this->log, [['part' => 1], ['part' => 2]]);
        $tracks   = new TracksImporter('tracks', [SplittingImporter::class], $this->log);

        $import = $this->graph([$schedule, $tracks])->start();
        $this->workOne();
        $this->bus->take();

        // both workers mark their job done, then both see no job left
        $this->connection->executeStatement("UPDATE import_job SET status = 'done'");

        $this->runner->endTask($this->taskId($import, 'schedule'));
        $this->runner->endTask($this->taskId($import, 'schedule'));

        $this->assertSame([[2, 2, 0]], $schedule->finished);
        $dispatched = $this->bus->take();
        $this->assertCount(1, $dispatched);
        $this->assertSame($this->taskId($import, 'tracks'), $dispatched[0]->taskId);
    }

    public function testJobsOfANodeOfASupersededImportDoNotRun(): void
    {
        $schedule = new SplittingImporter('schedule', [], $this->log, [['part' => 1], ['part' => 2]]);
        $graph    = $this->graph([$schedule]);

        $previous = $graph->start();
        $planning = $this->bus->take();

        // the node plans while a new import supersedes this one
        $schedule->onImport = fn () => $graph->start();
        $this->runner->run($planning[0]->taskId);

        $this->assertSame(['schedule' => ImportTaskStatus::Skipped], $this->statuses($previous));
        $this->assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM import_job'));
        $this->assertSame([], array_filter($this->bus->messages, fn (object $message) => $message instanceof RunImportJob));
    }

    public function testJobsQueuedAfterTheirImportWasSupersededAreSkipped(): void
    {
        $schedule = new SplittingImporter('schedule', [], $this->log, [['part' => 1], ['part' => 2]]);
        $graph    = $this->graph([$schedule]);

        $previous = $graph->start();
        $this->workOne();

        // the new import committed after the node read its import as unfinished, so supersede missed the jobs
        $this->connection->executeStatement("UPDATE import SET finished_at = '2026-10-28 12:00:00', succeeded = false");
        $this->work();

        $this->assertSame([], $schedule->ran);
        $this->assertSame([], $schedule->finished);
        $this->assertSame(['skipped', 'skipped'], $this->connection->fetchFirstColumn('SELECT status FROM import_job'));
        $this->assertSame(['schedule' => ImportTaskStatus::Skipped], $this->statuses($previous));
    }

    public function testJobDeliveredTwiceRunsOnce(): void
    {
        $schedule = new SplittingImporter('schedule', [], $this->log, [['part' => 1], ['part' => 2]]);

        $this->graph([$schedule])->start();
        $this->workOne();

        $this->runner->runJob($this->bus->messages[0]->jobId);
        $this->runner->runJob($this->bus->messages[0]->jobId);

        $this->assertSame([['part' => 1]], $schedule->ran);
        $this->assertSame([[2, 1, 0]], $schedule->ended);
    }

    public function testRedeliveredJobEndsTheNodeItsFirstDeliveryDidNotEnd(): void
    {
        $schedule = new SplittingImporter('schedule', [], $this->log, [['part' => 1], ['part' => 2]]);
        $tracks   = new TracksImporter('tracks', [SplittingImporter::class], $this->log);

        $import = $this->graph([$schedule, $tracks])->start();
        $this->workOne();
        $jobs = $this->bus->take();

        // both jobs committed done, but the delivery of the last one failed before it ended the node
        $this->connection->executeStatement("UPDATE import_job SET status = 'done'");

        $this->runner->runJob($jobs[1]->jobId);
        $this->runner->runJob($jobs[1]->jobId);

        $this->assertSame([], $schedule->ran);
        $this->assertSame([[2, 2, 0]], $schedule->finished);
        $this->assertSame(['schedule' => ImportTaskStatus::Done, 'tracks' => ImportTaskStatus::Queued], $this->statuses($import));
        $dispatched = $this->bus->take();
        $this->assertCount(1, $dispatched);
        $this->assertSame($this->taskId($import, 'tracks'), $dispatched[0]->taskId);
    }

    public function testRedeliveredJobDispatchesWhatItsEndedNodeDidNot(): void
    {
        $schedule = new SplittingImporter('schedule', [], $this->log, [['part' => 1]]);
        $tracks   = new TracksImporter('tracks', [SplittingImporter::class], $this->log);

        $import = $this->graph([$schedule, $tracks])->start();
        $this->workOne();
        $jobs = $this->bus->take();

        // the node ended, but the delivery failed before it dispatched its dependents
        $this->connection->executeStatement("UPDATE import_job SET status = 'done'");
        $this->connection->executeStatement("UPDATE import_task SET status = 'done' WHERE description = 'schedule'");

        $this->runner->runJob($jobs[0]->jobId);

        $this->assertSame([], $schedule->finished);
        $dispatched = $this->bus->take();
        $this->assertCount(1, $dispatched);
        $this->assertSame($this->taskId($import, 'tracks'), $dispatched[0]->taskId);
    }

    public function testRedeliveredTaskDispatchesItsQueuedJobs(): void
    {
        $schedule = new SplittingImporter('schedule', [], $this->log, [['part' => 1], ['part' => 2]]);

        $this->graph([$schedule])->start();
        $task = $this->bus->take()[0];

        // the jobs committed, but the delivery failed before it dispatched them
        $this->runner->run($task->taskId);
        $this->bus->take();

        $this->runner->run($task->taskId);

        $this->assertSame(['schedule'], $this->order());
        $this->assertSame([['part' => 1], ['part' => 2]], array_map($this->jobPayload(...), $this->bus->messages));

        $this->work();
        $this->assertSame([['part' => 1], ['part' => 2]], $schedule->ran);
        $this->assertSame([[2, 2, 0]], $schedule->finished);
    }

    public function testRedeliveredTaskDispatchesWhatItsEndedNodeDidNot(): void
    {
        $lines = new LinesImporter('lines', [], $this->log);
        $stops = new StopsImporter('stops', [LinesImporter::class], $this->log);

        $import = $this->graph([$lines, $stops])->start();
        $task   = $this->bus->take()[0];

        // the node ended, but the delivery failed before it dispatched its dependents
        $this->connection->executeStatement("UPDATE import_task SET status = 'done' WHERE description = 'lines'");

        $this->runner->run($task->taskId);

        $this->assertSame([], $this->order());
        $dispatched = $this->bus->take();
        $this->assertCount(1, $dispatched);
        $this->assertSame($this->taskId($import, 'stops'), $dispatched[0]->taskId);
    }

    public function testFailedJobsAreCountedAndTheImporterDecides(): void
    {
        $schedule = new SplittingImporter('schedule', [], $this->log, [['part' => 1, 'fail' => true], ['part' => 2]], succeeds: false);
        $tracks   = new TracksImporter('tracks', [SplittingImporter::class], $this->log);

        $import = $this->graph([$schedule, $tracks])->start();
        $this->work();

        $this->assertSame([[2, 1, 1]], $schedule->finished);
        $this->assertSame(['schedule' => ImportTaskStatus::Failed, 'tracks' => ImportTaskStatus::Skipped], $this->statuses($import));
        $this->assertSame(
            [['status' => 'failed', 'error' => 'Part 1 failed'], ['status' => 'done', 'error' => null]],
            $this->connection->fetchAllAssociative('SELECT status, error FROM import_job ORDER BY id'),
        );
        $this->assertNotNull($this->import($import)['finished_at']);
        $this->assertFalse((bool) $this->import($import)['succeeded']);
    }

    public function testJobEndedReportsCountsAfterEachJob(): void
    {
        $schedule = new SplittingImporter('schedule', [], $this->log, [['part' => 1], ['part' => 2, 'fail' => true], ['part' => 3]]);

        $import = $this->graph([$schedule])->start();
        $this->work();

        $this->assertSame([[3, 1, 0], [3, 1, 1], [3, 2, 1]], $schedule->ended);
        $this->assertSame(['schedule' => ImportTaskStatus::Done], $this->statuses($import));
        $this->assertTrue((bool) $this->import($import)['succeeded']);
    }

    public function testSupersedingSkipsQueuedJobs(): void
    {
        $schedule = new SplittingImporter('schedule', [], $this->log, [['part' => 1], ['part' => 2]]);
        $graph    = $this->graph([$schedule]);

        $previous = $graph->start();
        $this->workOne();
        $jobs = $this->bus->take();

        $graph->start();

        $this->assertSame(['skipped', 'skipped'], $this->connection->fetchFirstColumn('SELECT status FROM import_job'));

        // messages of the superseded jobs are still delivered, but do nothing
        foreach ($jobs as $job) {
            $this->runner->runJob($job->jobId);
        }

        $this->assertSame([], $schedule->ran);
        $this->assertSame(['schedule' => ImportTaskStatus::Skipped], $this->statuses($previous));
    }

    public function testRunSynchronouslyRunsJobs(): void
    {
        $provider = new ProviderImporter('provider', [], $this->log);
        $schedule = new SplittingImporter('schedule', [ProviderImporter::class], $this->log, [['part' => 1], ['part' => 2]]);
        $tracks   = new TracksImporter('tracks', [SplittingImporter::class], $this->log);

        $import = $this->graph([$tracks, $schedule, $provider])->runSynchronously();

        $this->assertSame([], $this->bus->messages);
        $this->assertSame(['provider', 'schedule', 'tracks'], $this->order());
        $this->assertSame([['part' => 1], ['part' => 2]], $schedule->ran);
        $this->assertSame(
            ['provider' => ImportTaskStatus::Done, 'schedule' => ImportTaskStatus::Done, 'tracks' => ImportTaskStatus::Done],
            $this->statuses($import),
        );
        $this->assertTrue($import->isSucceeded());
    }

    public function testNonSplittingImporterCannotAddJobs(): void
    {
        $lines = new class('lines', [], $this->log) extends RecordingImporter {
            #[\Override]
            public function import(ProgressReporterInterface $reporter, DataUpdateEvent $event): void
            {
                $event->addJob(['part' => 1]);
            }
        };

        $import = $this->graph([$lines])->start();
        $this->work();

        $this->assertSame(['lines' => ImportTaskStatus::Failed], $this->statuses($import));
        $this->assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM import_job'));
        $this->assertFalse((bool) $this->import($import)['succeeded']);
    }

    public function testJobCannotAddJobs(): void
    {
        $schedule = new class('schedule', [], $this->log) extends RecordingImporter implements SplittingDataImporter {
            #[\Override]
            public function import(ProgressReporterInterface $reporter, DataUpdateEvent $event): void
            {
                $event->addJob(['part' => 1]);
            }

            #[\Override]
            public function runJob(ProgressReporterInterface $reporter, DataUpdateEvent $event, array $payload): void
            {
                $event->addJob(['part' => 2]);
            }

            #[\Override]
            public function jobEnded(ProgressReporterInterface $reporter, ImportJobCounts $counts): void
            {
            }

            #[\Override]
            public function jobsEnded(ProgressReporterInterface $reporter, ImportJobCounts $counts): bool
            {
                return $counts->failed === 0;
            }
        };

        $import = $this->graph([$schedule])->start();
        $this->work();

        $this->assertSame(['failed'], $this->connection->fetchFirstColumn('SELECT status FROM import_job'));
        $this->assertStringContainsString('added jobs', (string) $this->connection->fetchOne('SELECT error FROM import_job'));
        $this->assertSame(['schedule' => ImportTaskStatus::Failed], $this->statuses($import));
    }

    /**
     * @param list<DataImporter> $importers
     */
    private function graph(array $importers, ?ClockInterface $clock = null): ImportGraph
    {
        $locator = new ServiceLocator(array_combine(
            array_map(fn (DataImporter $importer) => $importer::class, $importers),
            array_map(fn (DataImporter $importer) => fn () => $importer, $importers),
        ));

        $clock ??= new MockClock('2026-10-28 12:00:00');
        $reporter = new ProgressReporterFactory(new NullLogger());

        $graph        = new ImportGraph($this->em, $locator, $this->bus, $clock, fn () => $this->runner);
        $this->runner = new ImportTaskRunner($this->em, $locator, $graph, $reporter, $clock);

        return $graph;
    }

    /**
     * Runs dispatched messages until none is left, as workers would.
     */
    private function work(): void
    {
        while ($this->workOne()) {
        }
    }

    /**
     * Runs the first dispatched message, if any.
     */
    private function workOne(): bool
    {
        $message = array_shift($this->bus->messages);
        if ($message === null) {
            return false;
        }

        if ($message instanceof RunImportJob) {
            $this->runner->runJob($message->jobId);
        } else {
            $this->runner->run($message->taskId);
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function order(): array
    {
        return array_column($this->log->getArrayCopy(), 0);
    }

    /**
     * @return array<string, ImportTaskStatus> by description
     */
    private function statuses(ImportEntity $import): array
    {
        $rows = $this->connection->fetchAllKeyValue(
            'SELECT description, status FROM import_task WHERE import_id = :import ORDER BY description',
            ['import' => $import->getId()],
            ['import' => 'uuid'],
        );

        return array_map(ImportTaskStatus::from(...), $rows);
    }

    private function taskId(ImportEntity $import, string $description): string
    {
        $id = $this->connection->fetchOne(
            'SELECT id FROM import_task WHERE import_id = :import AND description = :description',
            ['import' => $import->getId(), 'description' => $description],
            ['import' => 'uuid'],
        );

        return Uuid::fromString($id)->toRfc4122();
    }

    private function jobPayload(RunImportJob $message): mixed
    {
        $payload = $this->connection->fetchOne(
            'SELECT payload FROM import_job WHERE id = :id',
            ['id' => $message->jobId],
            ['id' => 'uuid'],
        );

        return json_decode((string) $payload, true);
    }

    /**
     * @return array<string, mixed>
     */
    private function import(ImportEntity $import): array
    {
        return $this->connection->fetchAssociative(
            'SELECT started_at, finished_at, succeeded FROM import WHERE id = :id',
            ['id' => $import->getId()],
            ['id' => 'uuid'],
        );
    }
}

/**
 * A clock whose first readings deadlock, as the first statement of start()'s transaction would.
 */
final class DeadlockingClock implements ClockInterface
{
    public function __construct(
        private int $deadlocks,
    ) {
    }

    #[\Override]
    public function now(): \DateTimeImmutable
    {
        if ($this->deadlocks > 0) {
            $this->deadlocks--;

            throw new DeadlockException(PdoDriverException::new(new \PDOException('Deadlock found when trying to get lock')), null);
        }

        return new \DateTimeImmutable('2026-10-28 12:00:00');
    }
}

/**
 * Keeps dispatched messages instead of sending them.
 */
final class RecordingBus implements MessageBusInterface
{
    /**
     * @var list<RunImportTask|RunImportJob>
     */
    public array $messages = [];

    #[\Override]
    public function dispatch(object $message, array $stamps = []): Envelope
    {
        $this->messages[] = $message;

        return new Envelope($message, $stamps);
    }

    /**
     * @return list<RunImportTask|RunImportJob> messages dispatched since the last call
     */
    public function take(): array
    {
        $messages       = $this->messages;
        $this->messages = [];

        return $messages;
    }
}

abstract class RecordingImporter implements DataImporter
{
    public function __construct(
        protected readonly string $name,
        private readonly array $dependencies,
        protected readonly \ArrayObject $log,
    ) {
    }

    #[\Override]
    public function import(ProgressReporterInterface $reporter, DataUpdateEvent $event): void
    {
        $this->log[] = [$this->name, $event];
    }

    #[\Override]
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    #[\Override]
    public function getDescription(): string
    {
        return $this->name;
    }
}

final class ProviderImporter extends RecordingImporter
{
}

final class LinesImporter extends RecordingImporter
{
}

final class StopsImporter extends RecordingImporter
{
}

final class TracksImporter extends RecordingImporter
{
}

final class FailingImporter extends RecordingImporter
{
    #[\Override]
    public function import(ProgressReporterInterface $reporter, DataUpdateEvent $event): void
    {
        parent::import($reporter, $event);

        throw new \RuntimeException(sprintf('Feed of %s is unavailable', $this->name));
    }
}

/**
 * Hands out a job per given payload, which fails when its payload says so, and records what it is told about them.
 */
final class SplittingImporter extends RecordingImporter implements SplittingDataImporter
{
    /**
     * @var list<array<string, mixed>> payloads of the jobs run
     */
    public array $ran = [];

    /**
     * @var list<array{int, int, int}> total, done and failed jobs, after each job
     */
    public array $ended = [];

    /**
     * @var list<array{int, int, int}> total, done and failed jobs, after the last one
     */
    public array $finished = [];

    /**
     * @var ?\Closure(array<string, mixed>): void called while a job runs
     */
    public ?\Closure $onJob = null;

    /**
     * @var ?\Closure(): mixed called while the node plans
     */
    public ?\Closure $onImport = null;

    /**
     * @param list<array<string, mixed>> $parts
     */
    public function __construct(
        string $name,
        array $dependencies,
        \ArrayObject $log,
        private readonly array $parts,
        private readonly bool $succeeds = true,
    ) {
        parent::__construct($name, $dependencies, $log);
    }

    #[\Override]
    public function import(ProgressReporterInterface $reporter, DataUpdateEvent $event): void
    {
        parent::import($reporter, $event);

        if ($this->onImport !== null) {
            ($this->onImport)();
        }

        foreach ($this->parts as $part) {
            $event->addJob($part);
        }
    }

    #[\Override]
    public function runJob(ProgressReporterInterface $reporter, DataUpdateEvent $event, array $payload): void
    {
        $this->ran[] = $payload;

        if ($this->onJob !== null) {
            ($this->onJob)($payload);
        }

        if ($payload['fail'] ?? false) {
            throw new \RuntimeException(sprintf('Part %d failed', $payload['part']));
        }
    }

    #[\Override]
    public function jobEnded(ProgressReporterInterface $reporter, ImportJobCounts $counts): void
    {
        $this->ended[] = [$counts->total, $counts->done, $counts->failed];
    }

    #[\Override]
    public function jobsEnded(ProgressReporterInterface $reporter, ImportJobCounts $counts): bool
    {
        $this->finished[] = [$counts->total, $counts->done, $counts->failed];

        return $this->succeeds;
    }
}
