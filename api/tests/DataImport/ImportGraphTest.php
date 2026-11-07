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
use App\DataImport\ImportTaskRunner;
use App\DataImport\ImportTaskStatus;
use App\DataImport\ProgressReporterFactory;
use App\DataImport\ProgressReporterInterface;
use App\Entity\ImportEntity;
use App\Event\DataUpdateEvent;
use App\Message\RunImportTask;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

/**
 * The import is a graph of tasks, one per importer, run by workers as RunImportTask messages.
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

    public function testFailedSpawnedTaskStillSucceedsTheImport(): void
    {
        $planner = new PlanningImporter('planner', [], $this->log, [['part' => 1, 'fail' => true], ['part' => 2]]);

        $import = $this->graph([$planner])->start();
        $this->work();

        $this->assertSame(
            ['part 1' => ImportTaskStatus::Failed, 'part 2' => ImportTaskStatus::Done, 'planner' => ImportTaskStatus::Done],
            $this->statuses($import),
        );
        $this->assertNotNull($this->import($import)['finished_at']);
        $this->assertTrue((bool) $this->import($import)['succeeded']);
    }

    public function testImportFinishesAfterTheLastSpawnedTask(): void
    {
        $planner = new PlanningImporter('planner', [], $this->log, [['part' => 1], ['part' => 2]]);

        $import = $this->graph([$planner])->start();

        $this->workOne();
        $this->assertSame([['part' => 1], ['part' => 2]], array_map($this->payload(...), $this->bus->messages));
        $this->assertNull($this->import($import)['finished_at']);

        $this->workOne();
        $this->assertNull($this->import($import)['finished_at']);

        $this->workOne();
        $this->assertNotNull($this->import($import)['finished_at']);
        $this->assertSame(['planner', 'planner'], array_column($this->log->getArrayCopy(), 0));
        $this->assertSame(['part' => 2], $this->log[1][1]->payload);
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

    public function testRunSynchronouslyRunsEverything(): void
    {
        $provider = new ProviderImporter('provider', [], $this->log);
        $planner  = new PlanningImporter('planner', [ProviderImporter::class], $this->log, [['part' => 1], ['part' => 2]]);

        $import = $this->graph([$planner, $provider])->runSynchronously();

        $this->assertSame([], $this->bus->messages);
        $this->assertSame(['provider', 'planner', 'planner'], $this->order());
        $this->assertSame(
            [
                'part 1'   => ImportTaskStatus::Done,
                'part 2'   => ImportTaskStatus::Done,
                'planner'  => ImportTaskStatus::Done,
                'provider' => ImportTaskStatus::Done,
            ],
            $this->statuses($import),
        );
        $this->assertTrue($import->isSucceeded());
    }

    /**
     * @param list<DataImporter> $importers
     */
    private function graph(array $importers): ImportGraph
    {
        $locator = new ServiceLocator(array_combine(
            array_map(fn (DataImporter $importer) => $importer::class, $importers),
            array_map(fn (DataImporter $importer) => fn () => $importer, $importers),
        ));

        $clock    = new MockClock('2026-10-28 12:00:00');
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

        $this->runner->run($message->taskId);

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

    private function payload(RunImportTask $message): mixed
    {
        $payload = $this->connection->fetchOne(
            'SELECT payload FROM import_task WHERE id = :id',
            ['id' => $message->taskId],
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
 * Keeps dispatched messages instead of sending them.
 */
final class RecordingBus implements MessageBusInterface
{
    /**
     * @var list<RunImportTask>
     */
    public array $messages = [];

    #[\Override]
    public function dispatch(object $message, array $stamps = []): Envelope
    {
        $this->messages[] = $message;

        return new Envelope($message, $stamps);
    }

    /**
     * @return list<RunImportTask> messages dispatched since the last call
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
 * Spawns a task per given payload, which fails when its payload says so.
 */
final class PlanningImporter extends RecordingImporter
{
    /**
     * @param list<array<string, mixed>> $parts
     */
    public function __construct(
        string $name,
        array $dependencies,
        \ArrayObject $log,
        private readonly array $parts
    ) {
        parent::__construct($name, $dependencies, $log);
    }

    #[\Override]
    public function import(ProgressReporterInterface $reporter, DataUpdateEvent $event): void
    {
        if ($event->payload === null) {
            foreach ($this->parts as $part) {
                $event->spawn(self::class, $part, sprintf('part %d', $part['part']));
            }

            return;
        }

        parent::import($reporter, $event);

        if ($event->payload['fail'] ?? false) {
            throw new \RuntimeException('Part failed');
        }
    }
}
