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

use App\DataImport\PersistingProgressReporter;
use App\DataImport\ProgressReporterInterface;
use App\Entity\ImportEntity;
use App\Entity\ImportJobEntity;
use App\Entity\ImportTaskEntity;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;

final class PersistingProgressReporterTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private MockClock $clock;
    private ImportTaskEntity $task;

    /**
     * @var list<array{float, ?float, ?string, bool}> progress the wrapped reporter got
     */
    private array $reported = [];

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->em->getConnection()->beginTransaction();

        $this->clock = new MockClock('2026-10-28 12:00:00');
        $this->task  = $this->aTask();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->em->getConnection()->rollBack();

        parent::tearDown();
    }

    public function testProgressIsStoredAtMostOnceASecond(): void
    {
        $reporter = $this->reporterOfTask();

        $reporter->progress(1, max: 10, comment: 'line 1');
        $reporter->progress(2, max: 10, comment: 'line 2');
        $this->assertSame([1, 10, 'line 1'], $this->storedProgress('import_task'));

        $this->clock->sleep(1);
        $reporter->progress(3, max: 10, comment: 'line 3');
        $this->assertSame([3, 10, 'line 3'], $this->storedProgress('import_task'));

        // every report still reaches the wrapped reporter
        $this->assertCount(3, $this->reported);
    }

    public function testFinishedProgressIsAlwaysStored(): void
    {
        $reporter = $this->reporterOfTask();

        $reporter->progress(1, max: 10);
        $reporter->progress(10, max: 10, comment: 'OK', finished: true);

        $this->assertSame([10, 10, 'OK'], $this->storedProgress('import_task'));
    }

    public function testProgressOfAJobIsStoredOnTheJob(): void
    {
        $job = new ImportJobEntity($this->task, ['line' => '2']);
        $this->em->persist($job);
        $this->em->flush();

        PersistingProgressReporter::ofJob($job->getId(), $this->recordingReporter(), $this->em, $this->clock)
            ->progress(4, max: 7, comment: 'days', finished: true);

        $this->assertSame([4, 7, 'days'], $this->storedProgress('import_job'));
        $this->assertSame([0, null, null], $this->storedProgress('import_task'));
    }

    public function testProgressOfSubtasksIsNotStored(): void
    {
        $this->reporterOfTask()->subtask('Import tracks')->progress(5, max: 7, finished: true);

        $this->assertSame([0, null, null], $this->storedProgress('import_task'));
        $this->assertSame([[5.0, 7.0, null, true]], $this->reported);
    }

    private function reporterOfTask(): PersistingProgressReporter
    {
        return PersistingProgressReporter::ofTask($this->task->getId(), $this->recordingReporter(), $this->em, $this->clock);
    }

    private function recordingReporter(): ProgressReporterInterface
    {
        $reporter = $this->createStub(ProgressReporterInterface::class);
        $reporter->method('subtask')->willReturnCallback(fn () => $this->recordingReporter());
        $reporter->method('progress')->willReturnCallback(
            function (float $progress, ?float $max = null, ?string $comment = null, bool $finished = false): void {
                $this->reported[] = [$progress, $max, $comment, $finished];
            }
        );

        return $reporter;
    }

    private function aTask(): ImportTaskEntity
    {
        $import = new ImportEntity();
        $import->setStartedAt(Carbon::instance($this->clock->now()));

        $task = new ImportTaskEntity($import, importer: 'schedule', description: 'Schedule');

        $this->em->persist($import);
        $this->em->persist($task);
        $this->em->flush();

        return $task;
    }

    /**
     * @return array{int, ?int, ?string} progress, its maximum and comment stored in the only row of the table
     */
    private function storedProgress(string $table): array
    {
        $row = $this->em->getConnection()->fetchAssociative(sprintf('SELECT progress, progress_max, progress_comment FROM %s', $table));

        return [(int) $row['progress'], $row['progress_max'] === null ? null : (int) $row['progress_max'], $row['progress_comment']];
    }
}
