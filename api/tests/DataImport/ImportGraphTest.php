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
use App\DataImport\ImportStatus;
use App\DataImport\ImportTaskRunner;
use App\DataImport\ImportTaskStatus;
use App\DataImport\ProgressReporterFactory;
use App\DataImport\ProgressReporterInterface;
use App\DataImport\SplittingDataImporter;
use App\Entity\ImportEntity;
use App\Event\DataUpdateEvent;
use App\Event\ImportFinishedEvent;
use App\Event\ImportStartedEvent;
use App\Message\RunImportJob;
use App\Message\RunImportTask;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\Exception as PdoDriverException;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
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
 *
 * Importers are named after their class: LinesImporter is the task "lines".
 */
final class ImportGraphTest extends KernelTestCase
{
    private const string NOW = '2026-10-28 12:00:00';
    private EntityManagerInterface $em;
    private Connection $connection;
    private ImportTaskRunner $runner;

    /**
     * @var \ArrayObject<int, array{string, DataUpdateEvent}> importers that ran, with the event they got
     */
    private \ArrayObject $log;
    private RecordingBus $bus;
    private RecordingEvents $events;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();

        $this->em         = self::getContainer()->get(EntityManagerInterface::class);
        $this->connection = $this->em->getConnection();
        $this->connection->beginTransaction();

        $this->log    = new \ArrayObject();
        $this->bus    = new RecordingBus();
        $this->events = new RecordingEvents();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->connection->rollBack();

        parent::tearDown();
    }

    public function testImportersRunAfterTheirDependenciesAndFinishTheImport(): void
    {
        $stops = $this->importer(StopsImporter::class, dependsOn: [LinesImporter::class]);
        $lines = $this->importer(LinesImporter::class);

        $import = $this->graph($stops, $lines)->start();
        $this->work();

        $this->assertSame(['lines', 'stops'], $this->ran());
        $this->assertTrue($import->getId()->equals($this->importSeenBy('lines')->getId()));
        $this->assertSame(self::NOW, $this->importRow($import)['started_at']);
        $this->assertFinished($import, ImportStatus::Succeeded);
    }

    public function testImportRunsUntilItsLastTaskEnds(): void
    {
        $lines = $this->importer(LinesImporter::class);
        $stops = $this->importer(StopsImporter::class, dependsOn: [LinesImporter::class]);

        $import = $this->graph($lines, $stops)->start();
        $this->workOne();

        $this->assertSame(ImportStatus::Running->value, $this->importRow($import)['status']);
        $this->assertNull($this->importRow($import)['finished_at']);

        $this->work();

        $this->assertFinished($import, ImportStatus::Succeeded);
    }

    public function testStartAndEndOfAnImportAreAnnouncedOnce(): void
    {
        $graph  = $this->graph($this->importer(LinesImporter::class));
        $import = $graph->start();

        $this->assertSame([[ImportStartedEvent::class, $import, ImportStatus::Running]], $this->events->take());

        $this->work();
        // as a redelivered message of the last task would
        $graph->finishIfComplete($import);

        $this->assertSame([[ImportFinishedEvent::class, $import, ImportStatus::Succeeded]], $this->events->take());
    }

    public function testFailedImportIsAnnouncedAsEnded(): void
    {
        $import = $this->graph($this->importer(FailingImporter::class))->start();
        $this->work();

        $this->assertSame(
            [
                [ImportStartedEvent::class, $import, ImportStatus::Running],
                [ImportFinishedEvent::class, $import, ImportStatus::Failed],
            ],
            $this->events->take(),
        );
    }

    public function testSupersededImportIsAnnouncedAsEndedBeforeTheNewOneStarts(): void
    {
        $graph = $this->graph($this->importer(LinesImporter::class));

        $previous = $graph->start();
        $this->events->take();

        $current = $graph->start();

        $this->assertSame(
            [
                [ImportFinishedEvent::class, $previous, ImportStatus::Superseded],
                [ImportStartedEvent::class, $current, ImportStatus::Running],
            ],
            $this->events->take(),
        );
        $this->assertFinished($previous, ImportStatus::Superseded);
    }

    public function testProgressOfTasksAndJobsIsStored(): void
    {
        $schedule           = $this->schedule(jobs: [['part' => 1, 'progress' => 7]]);
        $schedule->onImport = fn (DataUpdateEvent $event, ProgressReporterInterface $reporter) => $reporter->progress(
            2,
            max: 3,
            comment: 'planned',
            finished: true,
        );

        $this->graph($schedule)->start();
        $this->work();

        $this->assertEquals(
            [['progress' => 2, 'progress_max' => 3, 'progress_comment' => 'planned']],
            $this->connection->fetchAllAssociative('SELECT progress, progress_max, progress_comment FROM import_task'),
        );
        $this->assertEquals(
            [['progress' => 7, 'progress_max' => 10, 'progress_comment' => 'part 1']],
            $this->connection->fetchAllAssociative('SELECT progress, progress_max, progress_comment FROM import_job'),
        );
    }

    public function testImporterRunsOnceAllOfItsDependenciesAreDone(): void
    {
        $provider = $this->importer(ProviderImporter::class);
        $stops    = $this->importer(StopsImporter::class, dependsOn: [ProviderImporter::class]);
        $lines    = $this->importer(LinesImporter::class, dependsOn: [ProviderImporter::class]);
        $tracks   = $this->importer(TracksImporter::class, dependsOn: [StopsImporter::class, LinesImporter::class]);

        $this->graph($tracks, $lines, $stops, $provider)->start();
        $this->work();

        [$first, $second, $third, $last] = $this->ran();
        $this->assertSame('provider', $first);
        $this->assertEqualsCanonicalizing(['stops', 'lines'], [$second, $third]);
        $this->assertSame('tracks', $last);
    }

    public function testMissingDependencyFailsTheStartBeforeAnythingIsWritten(): void
    {
        $lines = $this->importer(LinesImporter::class);
        $stops = $this->importer(StopsImporter::class, dependsOn: ['App\\NoSuchImporter']);

        try {
            $this->graph($lines, $stops)->start();
            $this->fail('The import should have failed.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString(StopsImporter::class, $exception->getMessage());
        }

        $this->assertSame(0, $this->rowsIn('import'));
        $this->assertSame(0, $this->rowsIn('import_task'));
        $this->assertSame([], $this->bus->messages);
    }

    public function testCircularDependenciesFailTheStart(): void
    {
        $lines = $this->importer(LinesImporter::class, dependsOn: [StopsImporter::class]);
        $stops = $this->importer(StopsImporter::class, dependsOn: [LinesImporter::class]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/LinesImporter.*StopsImporter|StopsImporter.*LinesImporter/');

        $this->graph($lines, $stops)->start();
    }

    public function testMemoryLimitIsRaisedWhileATaskRunsAndRestoredAfter(): void
    {
        $limitBefore = ini_get('memory_limit');
        $limitDuring = null;

        $lines           = $this->importer(LinesImporter::class);
        $lines->onImport = function () use (&$limitDuring): void {
            $limitDuring = ini_get('memory_limit');
        };

        try {
            $this->graph($lines)->start();
            $this->work();

            $this->assertSame('2G', $limitDuring);
            $this->assertSame($limitBefore, ini_get('memory_limit'));
        } finally {
            ini_set('memory_limit', $limitBefore);
        }
    }

    public function testTaskLeavingMoreMemoryInUseThanThePreviousLimitAllowsRaisesNoError(): void
    {
        $limitBefore = ini_get('memory_limit');
        ini_set('memory_limit', (string) (memory_get_usage(true) + (32 << 20)));

        $hoard           = '';
        $lines           = $this->importer(LinesImporter::class);
        $lines->onImport = function () use (&$hoard): void {
            $hoard = str_repeat('x', 64 << 20);
        };

        try {
            $errors = $this->errorsRaisedBy(function () use ($lines): void {
                $this->graph($lines)->start();
                $this->work();
            });

            $this->assertSame([], $errors);
        } finally {
            $hoard = '';
            ini_set('memory_limit', $limitBefore);
        }
    }

    public function testOnlyTasksWithoutDependenciesAreDispatchedAtStart(): void
    {
        $provider = $this->importer(ProviderImporter::class);
        $lines    = $this->importer(LinesImporter::class);
        $stops    = $this->importer(StopsImporter::class, dependsOn: [ProviderImporter::class]);

        $import = $this->graph($provider, $lines, $stops)->start();

        $this->assertCount(2, $this->bus->messages);
        $this->assertSame(
            [
                'lines'    => ImportTaskStatus::Queued,
                'provider' => ImportTaskStatus::Queued,
                'stops'    => ImportTaskStatus::Pending,
            ],
            $this->statuses($import),
        );
        $this->assertSame([], $this->ran());
    }

    public function testDependentIsDispatchedOnceWhenTwoDependenciesEndTogether(): void
    {
        $provider = $this->importer(ProviderImporter::class);
        $lines    = $this->importer(LinesImporter::class);
        $tracks   = $this->importer(TracksImporter::class, dependsOn: [ProviderImporter::class, LinesImporter::class]);

        $graph  = $this->graph($provider, $lines, $tracks);
        $import = $graph->start();
        $this->bus->take();

        // both workers mark their task done, then both look for ready tasks
        $this->markTasksDone('provider', 'lines');
        $graph->dispatchReady($import);
        $graph->dispatchReady($import);

        $this->assertDispatchedOnly($import, 'tracks');
    }

    public function testTaskDeliveredTwiceRunsOnce(): void
    {
        $this->graph($this->importer(LinesImporter::class))->start();
        [$task] = $this->bus->take();

        $this->runner->run($task->taskId);
        $this->runner->run($task->taskId);

        $this->assertSame(['lines'], $this->ran());
    }

    public function testFailedTaskSkipsItsDependentsAndFailsTheImport(): void
    {
        $provider = $this->importer(ProviderImporter::class);
        $failing  = $this->importer(FailingImporter::class, dependsOn: [ProviderImporter::class]);
        $tracks   = $this->importer(TracksImporter::class, dependsOn: [FailingImporter::class]);
        $stops    = $this->importer(StopsImporter::class, dependsOn: [ProviderImporter::class]);

        $import = $this->graph($provider, $failing, $tracks, $stops)->start();
        $this->work();

        $this->assertSame(
            [
                'failing'  => ImportTaskStatus::Failed,
                'provider' => ImportTaskStatus::Done,
                'stops'    => ImportTaskStatus::Done,
                'tracks'   => ImportTaskStatus::Skipped,
            ],
            $this->statuses($import),
        );
        $this->assertNotContains('tracks', $this->ran());
        $this->assertSame('Feed is unavailable', $this->taskError('failing'));
        $this->assertFinished($import, ImportStatus::Failed);
    }

    public function testStartSupersedesAnUnfinishedImport(): void
    {
        $lines = $this->importer(LinesImporter::class);
        $stops = $this->importer(StopsImporter::class, dependsOn: [LinesImporter::class]);
        $graph = $this->graph($lines, $stops);

        $previous = $graph->start();
        $current  = $graph->start();

        $this->assertSame(
            ['lines' => ImportTaskStatus::Skipped, 'stops' => ImportTaskStatus::Skipped],
            $this->statuses($previous),
        );
        $this->assertFinished($previous, ImportStatus::Superseded);

        // the message of the superseded task is still delivered, but does nothing
        $this->work();

        $this->assertSame(['lines', 'stops'], $this->ran());
        $this->assertTrue($current->getId()->equals($this->importSeenBy('lines')->getId()));
    }

    public function testStartIsRetriedWhenItsTransactionDeadlocks(): void
    {
        // superseding can deadlock with a worker claiming a job of the superseded import
        $import = $this->graphOn(new DeadlockingClock(deadlocks: 2), $this->importer(LinesImporter::class))->start();

        $this->assertSame(1, $this->rowsIn('import'));
        $this->assertSame(['lines' => ImportTaskStatus::Queued], $this->statuses($import));
        $this->assertCount(1, $this->bus->messages);
    }

    public function testNodeWaitsForItsJobsAndItsDependentsWaitForIt(): void
    {
        $schedule = $this->schedule(jobs: self::parts(2));
        $tracks   = $this->importer(TracksImporter::class, dependsOn: [ScheduleImporter::class]);

        $import = $this->graph($schedule, $tracks)->start();

        // the node hands out its jobs
        $this->workOne();
        $this->assertSame(
            ['schedule' => ImportTaskStatus::Waiting, 'tracks' => ImportTaskStatus::Pending],
            $this->statuses($import),
        );
        $this->assertSame(self::parts(2), $this->dispatchedJobPayloads());

        // the first job ends
        $this->workOne();
        $this->assertSame(
            ['schedule' => ImportTaskStatus::Waiting, 'tracks' => ImportTaskStatus::Pending],
            $this->statuses($import),
        );
        $this->assertNull($this->importRow($import)['finished_at']);

        // the last job ends the node
        $this->workOne();
        $this->assertSame(
            ['schedule' => ImportTaskStatus::Done, 'tracks' => ImportTaskStatus::Queued],
            $this->statuses($import),
        );
        $this->assertEquals([new ImportJobCounts(total: 2, done: 2, failed: 0)], $schedule->countsAfterLastJob);

        $this->work();
        $this->assertSame(['schedule', 'tracks'], $this->ran());
        $this->assertFinished($import, ImportStatus::Succeeded);
    }

    #[DataProvider('jobsEndingWhileAnotherRuns')]
    public function testJobEndingWhileAnotherStillRunsDoesNotEndTheNode(int $running, int $ending): void
    {
        $schedule = $this->schedule(jobs: self::parts(2));
        $tracks   = $this->importer(TracksImporter::class, dependsOn: [ScheduleImporter::class]);

        $import = $this->graph($schedule, $tracks)->start();
        $this->workOne();
        $jobs = $this->takeJobsByPart();

        $schedule->whileRunningPart($running, fn () => $this->runner->runJob($jobs[$ending]));
        $this->runner->runJob($jobs[$running]);

        $this->assertEquals([new ImportJobCounts(total: 2, done: 2, failed: 0)], $schedule->countsAfterLastJob);
        $this->assertDispatchedOnly($import, 'tracks');
    }

    public static function jobsEndingWhileAnotherRuns(): iterable
    {
        yield 'second ends while the first runs' => [1, 2];
        yield 'first ends while the second runs' => [2, 1];
    }

    public function testNodeEndsOnceWhenItsLastJobsEndTogether(): void
    {
        $schedule = $this->schedule(jobs: self::parts(2));
        $tracks   = $this->importer(TracksImporter::class, dependsOn: [ScheduleImporter::class]);

        $import = $this->graph($schedule, $tracks)->start();
        $this->workOne();
        $this->bus->take();

        // both workers mark their job done, then both see no job left
        $this->markAllJobsDone();
        $this->runner->endTask($this->taskId($import, 'schedule'));
        $this->runner->endTask($this->taskId($import, 'schedule'));

        $this->assertEquals([new ImportJobCounts(total: 2, done: 2, failed: 0)], $schedule->countsAfterLastJob);
        $this->assertDispatchedOnly($import, 'tracks');
    }

    public function testNodeSupersededWhileHandingOutJobsQueuesNone(): void
    {
        $schedule = $this->schedule(jobs: self::parts(2));
        $graph    = $this->graph($schedule);

        $previous = $graph->start();
        [$task]   = $this->bus->take();

        $schedule->onImport = fn () => $graph->start();
        $this->runner->run($task->taskId);

        $this->assertSame(['schedule' => ImportTaskStatus::Skipped], $this->statuses($previous));
        $this->assertSame(0, $this->rowsIn('import_job'));
        $this->assertSame([], $this->dispatchedJobPayloads());
    }

    public function testJobsQueuedAfterTheirImportWasSupersededAreSkipped(): void
    {
        $schedule = $this->schedule(jobs: self::parts(2));

        $previous = $this->graph($schedule)->start();
        $this->workOne();

        // the new import committed after the node read its import as unfinished, so superseding missed the jobs
        $this->finishImportsWithoutSupersedingThem();
        $this->work();

        $this->assertSame([], $schedule->jobsRan);
        $this->assertSame([], $schedule->countsAfterLastJob);
        $this->assertSame(['skipped', 'skipped'], $this->jobStatuses());
        $this->assertSame(['schedule' => ImportTaskStatus::Skipped], $this->statuses($previous));
    }

    public function testJobDeliveredTwiceRunsOnce(): void
    {
        $schedule = $this->schedule(jobs: self::parts(2));

        $this->graph($schedule)->start();
        $this->workOne();
        $jobs = $this->takeJobsByPart();

        $this->runner->runJob($jobs[1]);
        $this->runner->runJob($jobs[1]);

        $this->assertSame([['part' => 1]], $schedule->jobsRan);
        $this->assertEquals([new ImportJobCounts(total: 2, done: 1, failed: 0)], $schedule->countsAfterEachJob);
    }

    public function testRedeliveredLastJobEndsTheNodeItsFirstDeliveryDidNot(): void
    {
        $schedule = $this->schedule(jobs: self::parts(2));
        $tracks   = $this->importer(TracksImporter::class, dependsOn: [ScheduleImporter::class]);

        $import = $this->graph($schedule, $tracks)->start();
        $this->workOne();
        $jobs = $this->takeJobsByPart();

        // both jobs committed done, but the delivery of the last one failed before it ended the node
        $this->markAllJobsDone();
        $this->runner->runJob($jobs[2]);

        $this->assertSame([], $schedule->jobsRan);
        $this->assertEquals([new ImportJobCounts(total: 2, done: 2, failed: 0)], $schedule->countsAfterLastJob);
        $this->assertSame(
            ['schedule' => ImportTaskStatus::Done, 'tracks' => ImportTaskStatus::Queued],
            $this->statuses($import),
        );
        $this->assertDispatchedOnly($import, 'tracks');
    }

    public function testRedeliveredJobDispatchesWhatItsEndedNodeDidNot(): void
    {
        $schedule = $this->schedule(jobs: self::parts(1));
        $tracks   = $this->importer(TracksImporter::class, dependsOn: [ScheduleImporter::class]);

        $import = $this->graph($schedule, $tracks)->start();
        $this->workOne();
        $jobs = $this->takeJobsByPart();

        // the node ended, but the delivery failed before it dispatched its dependents
        $this->markAllJobsDone();
        $this->markTasksDone('schedule');
        $this->runner->runJob($jobs[1]);

        $this->assertSame([], $schedule->countsAfterLastJob);
        $this->assertDispatchedOnly($import, 'tracks');
    }

    public function testRedeliveredTaskDispatchesItsQueuedJobs(): void
    {
        $schedule = $this->schedule(jobs: self::parts(2));

        $this->graph($schedule)->start();
        [$task] = $this->bus->take();

        // the jobs committed, but the delivery failed before it dispatched them
        $this->runner->run($task->taskId);
        $this->bus->take();

        $this->runner->run($task->taskId);

        $this->assertSame(['schedule'], $this->ran());
        $this->assertSame(self::parts(2), $this->dispatchedJobPayloads());

        $this->work();
        $this->assertSame(self::parts(2), $schedule->jobsRan);
        $this->assertEquals([new ImportJobCounts(total: 2, done: 2, failed: 0)], $schedule->countsAfterLastJob);
    }

    public function testRedeliveredTaskDispatchesWhatItsEndedNodeDidNot(): void
    {
        $lines = $this->importer(LinesImporter::class);
        $stops = $this->importer(StopsImporter::class, dependsOn: [LinesImporter::class]);

        $import = $this->graph($lines, $stops)->start();
        [$task] = $this->bus->take();

        // the node ended, but the delivery failed before it dispatched its dependents
        $this->markTasksDone('lines');
        $this->runner->run($task->taskId);

        $this->assertSame([], $this->ran());
        $this->assertDispatchedOnly($import, 'stops');
    }

    public function testImporterDecidesHowANodeWithFailedJobsEnds(): void
    {
        $schedule = $this->schedule(jobs: [['part' => 1, 'fail' => true], ['part' => 2]], succeeds: false);
        $tracks   = $this->importer(TracksImporter::class, dependsOn: [ScheduleImporter::class]);

        $import = $this->graph($schedule, $tracks)->start();
        $this->work();

        $this->assertEquals([new ImportJobCounts(total: 2, done: 1, failed: 1)], $schedule->countsAfterLastJob);
        $this->assertSame(
            ['schedule' => ImportTaskStatus::Failed, 'tracks' => ImportTaskStatus::Skipped],
            $this->statuses($import),
        );
        $this->assertSame(
            [['status' => 'failed', 'error' => 'Part 1 failed'], ['status' => 'done', 'error' => null]],
            $this->connection->fetchAllAssociative('SELECT status, error FROM import_job ORDER BY id'),
        );
        $this->assertFinished($import, ImportStatus::Failed);
    }

    public function testImporterIsToldTheCountsAfterEachJob(): void
    {
        $schedule = $this->schedule(jobs: [['part' => 1], ['part' => 2, 'fail' => true], ['part' => 3]]);

        $import = $this->graph($schedule)->start();
        $this->work();

        $this->assertEquals(
            [
                new ImportJobCounts(total: 3, done: 1, failed: 0),
                new ImportJobCounts(total: 3, done: 1, failed: 1),
                new ImportJobCounts(total: 3, done: 2, failed: 1),
            ],
            $schedule->countsAfterEachJob,
        );
        $this->assertSame(['schedule' => ImportTaskStatus::Done], $this->statuses($import));
        $this->assertFinished($import, ImportStatus::Succeeded);
    }

    public function testSupersedingSkipsQueuedJobs(): void
    {
        $schedule = $this->schedule(jobs: self::parts(2));
        $graph    = $this->graph($schedule);

        $previous = $graph->start();
        $this->workOne();
        $jobs = $this->takeJobsByPart();

        $graph->start();

        $this->assertSame(['skipped', 'skipped'], $this->jobStatuses());

        // messages of the superseded jobs are still delivered, but do nothing
        array_map($this->runner->runJob(...), $jobs);

        $this->assertSame([], $schedule->jobsRan);
        $this->assertSame(['schedule' => ImportTaskStatus::Skipped], $this->statuses($previous));
    }

    public function testRunSynchronouslyRunsTasksAndJobsWithoutWorkers(): void
    {
        $provider = $this->importer(ProviderImporter::class);
        $schedule = $this->schedule(jobs: self::parts(2), dependsOn: [ProviderImporter::class]);
        $tracks   = $this->importer(TracksImporter::class, dependsOn: [ScheduleImporter::class]);

        $import = $this->graph($tracks, $schedule, $provider)->runSynchronously();

        $this->assertSame([], $this->bus->messages);
        $this->assertSame(['provider', 'schedule', 'tracks'], $this->ran());
        $this->assertSame(self::parts(2), $schedule->jobsRan);
        $this->assertSame(
            [
                'provider' => ImportTaskStatus::Done,
                'schedule' => ImportTaskStatus::Done,
                'tracks'   => ImportTaskStatus::Done,
            ],
            $this->statuses($import),
        );
        $this->assertSame(ImportStatus::Succeeded, $import->getStatus());
    }

    public function testImporterThatDoesNotSplitCannotAddJobs(): void
    {
        $lines           = $this->importer(LinesImporter::class);
        $lines->onImport = fn (DataUpdateEvent $event) => $event->addJob(['part' => 1]);

        $import = $this->graph($lines)->start();
        $this->work();

        $this->assertSame(['lines' => ImportTaskStatus::Failed], $this->statuses($import));
        $this->assertSame(0, $this->rowsIn('import_job'));
        $this->assertFinished($import, ImportStatus::Failed);
    }

    public function testJobAddingJobsFails(): void
    {
        $schedule = $this->schedule(jobs: [['part' => 1, 'addsJob' => true]]);

        $this->graph($schedule)->start();
        $this->work();

        $this->assertSame(['failed'], $this->jobStatuses());
        $error = (string) $this->connection->fetchOne('SELECT error FROM import_job');
        $this->assertStringContainsString('added jobs', $error);
    }

    /**
     * @template T of RecordingImporter
     *
     * @param class-string<T> $class
     * @param list<string>    $dependsOn classes of the importers it depends on
     *
     * @return T
     */
    private function importer(string $class, array $dependsOn = []): RecordingImporter
    {
        return new $class($dependsOn, $this->log);
    }

    /**
     * @param list<array<string, mixed>> $jobs      payloads of the jobs it hands out
     * @param list<string>               $dependsOn
     * @param bool                       $succeeds  how it decides the node ends
     */
    private function schedule(array $jobs, array $dependsOn = [], bool $succeeds = true): ScheduleImporter
    {
        return new ScheduleImporter($dependsOn, $this->log, $jobs, $succeeds);
    }

    /**
     * @return list<array{part: int}>
     */
    private static function parts(int $count): array
    {
        return array_map(fn (int $part) => ['part' => $part], range(1, $count));
    }

    private function graph(DataImporter ...$importers): ImportGraph
    {
        return $this->graphOn(new MockClock(self::NOW), ...$importers);
    }

    private function graphOn(ClockInterface $clock, DataImporter ...$importers): ImportGraph
    {
        $locator = new ServiceLocator(array_combine(
            array_map(fn (DataImporter $importer) => $importer::class, $importers),
            array_map(fn (DataImporter $importer) => fn () => $importer, $importers),
        ));

        $graph        = new ImportGraph($this->em, $locator, $this->bus, $this->events, $clock, fn () => $this->runner);
        $this->runner = new ImportTaskRunner(
            $this->em,
            $locator,
            $graph,
            new ProgressReporterFactory(new NullLogger()),
            $clock,
        );

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

        match (true) {
            $message instanceof RunImportJob  => $this->runner->runJob($message->jobId),
            $message instanceof RunImportTask => $this->runner->run($message->taskId),
            default                           => null,
        };

        return $message !== null;
    }

    /**
     * @return array<int, string> ids of the jobs dispatched since the last take, by the part in their payload
     */
    private function takeJobsByPart(): array
    {
        $jobs = $this->bus->take();

        return array_combine(
            array_map(fn (RunImportJob $job) => $this->jobPayload($job)['part'], $jobs),
            array_map(fn (RunImportJob $job) => $job->jobId, $jobs),
        );
    }

    private function markTasksDone(string ...$descriptions): void
    {
        foreach ($descriptions as $description) {
            $this->connection->executeStatement(
                "UPDATE import_task SET status = 'done' WHERE description = ?",
                [$description],
            );
        }
    }

    private function markAllJobsDone(): void
    {
        $this->connection->executeStatement("UPDATE import_job SET status = 'done'");
    }

    private function finishImportsWithoutSupersedingThem(): void
    {
        $this->connection->executeStatement("UPDATE import SET finished_at = ?, status = 'failed'", [self::NOW]);
    }

    /**
     * @return list<string> descriptions of the importers that ran, in order
     */
    private function ran(): array
    {
        return array_column($this->log->getArrayCopy(), 0);
    }

    private function importSeenBy(string $description): ImportEntity
    {
        $events = array_column($this->log->getArrayCopy(), 1, 0);

        return $events[$description]->import;
    }

    private function assertDispatchedOnly(ImportEntity $import, string $task): void
    {
        $this->assertEquals([new RunImportTask($this->taskId($import, $task))], $this->bus->take());
    }

    private function assertFinished(ImportEntity $import, ImportStatus $status): void
    {
        $row = $this->importRow($import);

        $this->assertSame(self::NOW, $row['finished_at']);
        $this->assertSame($status->value, $row['status']);
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

    private function taskError(string $description): ?string
    {
        return $this->connection->fetchOne('SELECT error FROM import_task WHERE description = ?', [$description]);
    }

    /**
     * @return list<string>
     */
    private function jobStatuses(): array
    {
        return $this->connection->fetchFirstColumn('SELECT status FROM import_job ORDER BY id');
    }

    /**
     * @return list<mixed> payloads of the jobs dispatched and not yet run
     */
    private function dispatchedJobPayloads(): array
    {
        $jobs = array_filter($this->bus->messages, fn (object $message) => $message instanceof RunImportJob);

        return array_values(array_map($this->jobPayload(...), $jobs));
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
     * @return array{started_at: string, finished_at: ?string, status: string}
     */
    private function importRow(ImportEntity $import): array
    {
        return $this->connection->fetchAssociative(
            'SELECT started_at, finished_at, status FROM import WHERE id = :id',
            ['id' => $import->getId()],
            ['id' => 'uuid'],
        );
    }

    private function rowsIn(string $table): int
    {
        return (int) $this->connection->fetchOne(sprintf('SELECT COUNT(*) FROM %s', $table));
    }

    /**
     * @return list<string> messages of the errors PHP raised meanwhile
     */
    private function errorsRaisedBy(\Closure $action): array
    {
        $errors = [];
        set_error_handler(function (int $level, string $message) use (&$errors): bool {
            $errors[] = $message;

            return true;
        });

        try {
            $action();
        } finally {
            restore_error_handler();
        }

        return $errors;
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

            throw new DeadlockException(
                PdoDriverException::new(new \PDOException('Deadlock found when trying to get lock')),
                null,
            );
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

/**
 * Keeps the import events dispatched, with the status of their import when dispatched.
 */
final class RecordingEvents implements EventDispatcherInterface
{
    /**
     * @var list<array{class-string, ImportEntity, ImportStatus}>
     */
    private array $events = [];

    #[\Override]
    public function dispatch(object $event): object
    {
        if ($event instanceof ImportStartedEvent || $event instanceof ImportFinishedEvent) {
            $this->events[] = [$event::class, $event->import, $event->import->getStatus()];
        }

        return $event;
    }

    /**
     * @return list<array{class-string, ImportEntity, ImportStatus}> events dispatched since the last call
     */
    public function take(): array
    {
        $events       = $this->events;
        $this->events = [];

        return $events;
    }
}

/**
 * Logs that it ran, under its class name without "Importer" (LinesImporter logs "lines").
 */
abstract class RecordingImporter implements DataImporter
{
    /**
     * @var ?\Closure(DataUpdateEvent, ProgressReporterInterface): mixed called while it runs
     */
    public ?\Closure $onImport = null;

    /**
     * @param list<string> $dependencies
     */
    public function __construct(
        private readonly array $dependencies,
        private readonly \ArrayObject $log,
    ) {
    }

    #[\Override]
    public function import(ProgressReporterInterface $reporter, DataUpdateEvent $event): void
    {
        $this->log[] = [$this->getDescription(), $event];

        if ($this->onImport !== null) {
            ($this->onImport)($event, $reporter);
        }
    }

    #[\Override]
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    #[\Override]
    public function getDescription(): string
    {
        return strtolower(substr(new \ReflectionClass($this)->getShortName(), 0, -strlen('Importer')));
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

        throw new \RuntimeException('Feed is unavailable');
    }
}

/**
 * Hands out a job per given payload and records what it is told about them. A job fails when its payload has
 * `fail`, adds a job when it has `addsJob` and reports the `progress` it has.
 */
final class ScheduleImporter extends RecordingImporter implements SplittingDataImporter
{
    /**
     * @var list<array<string, mixed>> payloads of the jobs run
     */
    public array $jobsRan = [];

    /**
     * @var list<ImportJobCounts> what jobEnded() was told
     */
    public array $countsAfterEachJob = [];

    /**
     * @var list<ImportJobCounts> what jobsEnded() was told
     */
    public array $countsAfterLastJob = [];

    /**
     * @var array<int, \Closure(): mixed> run once while the job of the part runs, by part
     */
    private array $whileRunning = [];

    /**
     * @param list<array<string, mixed>> $jobs
     */
    public function __construct(
        array $dependencies,
        \ArrayObject $log,
        private readonly array $jobs,
        private readonly bool $succeeds,
    ) {
        parent::__construct($dependencies, $log);
    }

    /**
     * Runs the action while the job of the part runs, as another worker would.
     */
    public function whileRunningPart(int $part, \Closure $action): void
    {
        $this->whileRunning[$part] = $action;
    }

    #[\Override]
    public function import(ProgressReporterInterface $reporter, DataUpdateEvent $event): void
    {
        parent::import($reporter, $event);

        foreach ($this->jobs as $payload) {
            $event->addJob($payload);
        }
    }

    #[\Override]
    public function runJob(ProgressReporterInterface $reporter, DataUpdateEvent $event, array $payload): void
    {
        $this->jobsRan[] = $payload;

        $this->runActionWhileRunning($payload['part']);

        if (isset($payload['progress'])) {
            $reporter->progress(
                $payload['progress'],
                max: 10,
                comment: sprintf('part %d', $payload['part']),
                finished: true,
            );
        }

        if ($payload['addsJob'] ?? false) {
            $event->addJob(['part' => $payload['part'] + 1]);
        }

        if ($payload['fail'] ?? false) {
            throw new \RuntimeException(sprintf('Part %d failed', $payload['part']));
        }
    }

    #[\Override]
    public function jobEnded(ProgressReporterInterface $reporter, ImportJobCounts $counts): void
    {
        $this->countsAfterEachJob[] = $counts;
    }

    #[\Override]
    public function jobsEnded(ProgressReporterInterface $reporter, ImportJobCounts $counts): bool
    {
        $this->countsAfterLastJob[] = $counts;

        return $this->succeeds;
    }

    private function runActionWhileRunning(int $part): void
    {
        $action = $this->whileRunning[$part] ?? null;
        unset($this->whileRunning[$part]);

        if ($action !== null) {
            $action();
        }
    }
}
