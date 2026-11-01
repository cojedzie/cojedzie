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

namespace App\Tests\MessageHandler;

use App\DepartureHistory\DepartureRecorder;
use App\Message\RecordDepartures;
use App\MessageHandler\RecordDeparturesMessageHandler;
use App\Provider\Live\LiveDepartureSource;
use App\Schedule\ScheduleConfig;
use App\Tests\DepartureHistory\Support\FakeLiveDepartureSource;
use App\Tests\DepartureHistory\Support\InMemoryDepartureHistory;
use Ds\Seq;
use PHPUnit\Framework\TestCase;
use Sentry\CheckInStatus;
use Sentry\MonitorConfig;
use Sentry\State\HubInterface;
use Symfony\Component\Clock\MockClock;

final class RecordDeparturesMessageHandlerTest extends TestCase
{
    private MockClock $clock;
    private InMemoryDepartureHistory $history;

    /**
     * @var list<array{string, CheckInStatus, ?MonitorConfig}>
     */
    private array $checkIns = [];

    #[\Override]
    protected function setUp(): void
    {
        $this->clock   = new MockClock('2026-11-01 11:00:40 UTC');
        $this->history = new InMemoryDepartureHistory();
    }

    public function testDoesNothingWhenDisabled(): void
    {
        $this->history = new InMemoryDepartureHistory(enabled: false);

        $this->handler(FakeLiveDepartureSource::with('trojmiasto'))(new RecordDepartures());

        $this->assertSame(0, $this->history->prepared);
        $this->assertSame([], $this->history->recorded);
    }

    public function testHeartbeatAtMostOncePerMinute(): void
    {
        $handler = $this->handler(FakeLiveDepartureSource::with('trojmiasto'));

        foreach ([0, 20, 20, 20] as $seconds) {
            $this->clock->sleep($seconds);
            $handler(new RecordDepartures());
        }

        $this->assertCount(4, $this->history->recorded);
        $this->assertCount(2, $this->checkIns);
        foreach ($this->checkIns as [$slug, $status, $config]) {
            $this->assertSame('record-departures', $slug);
            $this->assertSame(CheckInStatus::ok(), $status);
            $this->assertSame(1, $config?->getSchedule()->getValue());
        }
    }

    public function testNoHeartbeatWhenAProviderFailed(): void
    {
        $this->handler(FakeLiveDepartureSource::failing('trojmiasto'))(new RecordDepartures());

        $this->assertSame([], $this->checkIns);
    }

    public function testSkipsPollStartedTooSoonAfterThePrevious(): void
    {
        $handler = $this->handler(FakeLiveDepartureSource::with('trojmiasto'));

        $handler(new RecordDepartures());
        $this->clock->sleep(5);
        $handler(new RecordDepartures());

        $this->assertCount(1, $this->history->recorded);
    }

    public function testSlowPollDoesNotSkipTheNext(): void
    {
        $handler = $this->handler(FakeLiveDepartureSource::using('trojmiasto', function (): Seq {
            $this->clock->sleep(12);

            return new Seq();
        }));

        $handler(new RecordDepartures());
        $this->clock->sleep(8);
        $handler(new RecordDepartures());

        $this->assertCount(2, $this->history->recorded);
    }

    private function handler(LiveDepartureSource ...$sources): RecordDeparturesMessageHandler
    {
        $hub = $this->createStub(HubInterface::class);
        $hub->method('captureCheckIn')->willReturnCallback(
            function (string $slug, CheckInStatus $status, $duration = null, ?MonitorConfig $config = null): string {
                $this->checkIns[] = [$slug, $status, $config];

                return 'check-in-id';
            }
        );

        $handler = new RecordDeparturesMessageHandler(
            new DepartureRecorder($sources, $this->history, $this->clock),
            $this->clock,
            new ScheduleConfig(
                updateDataSources: '0 5 * * *',
                federationCheck: '* * * * *',
                federationCleanup: '0 0 * * *',
                collectStaleData: '0 6 * * *',
                recordDepartures: '20 seconds',
            ),
        );
        $handler->setSentryHub($hub);

        return $handler;
    }
}
