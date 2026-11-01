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

namespace App\Tests\DepartureHistory;

use App\DepartureHistory\DepartureRecorder;
use App\Provider\Live\LiveDepartureSource;
use App\Tests\DepartureHistory\Support\FakeLiveDepartureSource;
use App\Tests\DepartureHistory\Support\InMemoryDepartureHistory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\ErrorHandler\BufferingLogger;

final class DepartureRecorderTest extends TestCase
{
    private InMemoryDepartureHistory $history;
    private BufferingLogger $logger;

    #[\Override]
    protected function setUp(): void
    {
        $this->history = new InMemoryDepartureHistory();
        $this->logger  = new BufferingLogger();
    }

    public function testRecordsEverySourceAsObservedNow(): void
    {
        $succeeded = $this->recorder(
            FakeLiveDepartureSource::with('trojmiasto', FakeLiveDepartureSource::departure('8227'), FakeLiveDepartureSource::departure('114')),
            FakeLiveDepartureSource::with('poznan', FakeLiveDepartureSource::departure('1')),
        )->record();

        $this->assertTrue($succeeded);
        $this->assertSame(['trojmiasto', 'poznan'], array_column($this->history->recorded, 'provider'));
        $this->assertCount(2, $this->history->recorded[0]['departures']);
        $this->assertEquals(new \DateTimeImmutable('2026-11-01 11:00:40 UTC'), $this->history->recorded[0]['observedAt']);
        $this->assertSame([], $this->logger->cleanLogs());
    }

    public function testFailingSourceDoesNotStopTheOthers(): void
    {
        $succeeded = $this->recorder(
            FakeLiveDepartureSource::failing('trojmiasto'),
            FakeLiveDepartureSource::with('poznan', FakeLiveDepartureSource::departure()),
        )->record();

        $this->assertFalse($succeeded);
        $this->assertSame(['poznan'], array_column($this->history->recorded, 'provider'));
        $this->assertWarning('trojmiasto is down');
    }

    public function testFailingStorageIsAWarning(): void
    {
        $this->history->failingRecords = true;

        $this->assertFalse($this->recorder(FakeLiveDepartureSource::with('trojmiasto', FakeLiveDepartureSource::departure()))->record());
        $this->assertWarning('Storage is down');
    }

    public function testStorageIsPreparedOncePerProcess(): void
    {
        $recorder = $this->recorder(FakeLiveDepartureSource::with('trojmiasto'));

        $recorder->record();
        $recorder->record();

        $this->assertSame(1, $this->history->prepared);
    }

    public function testPreparationIsRetriedAfterItFailed(): void
    {
        $this->history->failingPreparations = 1;
        $recorder                           = $this->recorder(FakeLiveDepartureSource::with('trojmiasto', FakeLiveDepartureSource::departure()));

        $this->assertFalse($recorder->record());
        $this->assertSame([], $this->history->recorded);
        $this->assertWarning('Storage is down');

        $this->assertTrue($recorder->record());
        $this->assertCount(1, $this->history->recorded);
    }

    private function recorder(LiveDepartureSource ...$sources): DepartureRecorder
    {
        $recorder = new DepartureRecorder($sources, $this->history, new MockClock('2026-11-01 11:00:40 UTC'));
        $recorder->setLogger($this->logger);

        return $recorder;
    }

    private function assertWarning(string $message): void
    {
        $warnings = array_filter($this->logger->cleanLogs(), fn (array $log) => $log[0] === 'warning');

        $this->assertCount(1, $warnings);
        $this->assertStringContainsString($message, (string) array_first($warnings)[2]['message']);
    }
}
