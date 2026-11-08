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

namespace App\Tests\ClickHouse;

use App\ClickHouse\ClickHouseConnection;
use App\ClickHouse\ClickHouseDepartureHistory;
use App\DepartureHistory\DepartureHistoryException;
use App\Provider\Live\DepartureStatus;
use App\Provider\Live\LiveDeparture;
use App\Tests\ClickHouse\Support\TestClickHouse;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

final class ClickHouseDepartureHistoryTest extends TestCase
{
    #[Group('clickhouse')]
    public function testNewestObservationOfADepartureWins(): void
    {
        [$history, $connection] = $this->history();

        // a slow poll can be inserted after the next one
        $history->record('trojmiasto', new \DateTimeImmutable('2026-11-01 11:00:20 UTC'), [$this->departure('2026-11-01T11:12:03+00:00')]);
        $history->record('trojmiasto', new \DateTimeImmutable('2026-11-01 11:00:00 UTC'), [$this->departure('2026-11-01T11:14:00Z')]);

        $this->assertSame([[
            'provider'     => 'trojmiasto',
            'stop_id'      => '8227',
            'line_symbol'  => '171',
            'scheduled_at' => '2026-11-01 11:13:00',
            'estimated_at' => '2026-11-01 11:12:03',
            'delay'        => -57,
            'status'       => 'realtime',
            'vehicle'      => '9221',
            'duty'         => '171-01',
            'observed_at'  => '2026-11-01 11:00:20',
        ]], $connection->client()->select(
            'SELECT provider, stop_id, line_symbol, scheduled_at, estimated_at, delay, status, vehicle, duty, observed_at FROM departures FINAL'
        )->rows());
    }

    #[Group('clickhouse')]
    public function testScheduledDepartureHasNoEstimate(): void
    {
        [$history, $connection] = $this->history();

        $history->record('trojmiasto', new \DateTimeImmutable('2026-11-01 11:00:00 UTC'), [$this->departure(null)]);

        $this->assertSame(
            [['estimated_at' => null, 'delay' => null, 'status' => 'scheduled', 'vehicle' => null, 'duty' => null]],
            $connection->client()->select('SELECT estimated_at, delay, status, vehicle, duty FROM departures FINAL')->rows(),
        );
    }

    #[Group('clickhouse')]
    public function testObservedReturnsTheNewestObservation(): void
    {
        [$history] = $this->history();

        $history->record('trojmiasto', new \DateTimeImmutable('2026-11-01 11:00:00 UTC'), [$this->departure('2026-11-01T11:14:00Z')]);
        $history->record('trojmiasto', new \DateTimeImmutable('2026-11-01 11:00:20 UTC'), [$this->departure('2026-11-01T11:12:03+00:00')]);

        $observed = $history->observed('trojmiasto', new \DateTimeImmutable('2026-11-01 00:00:00 UTC'), new \DateTimeImmutable('2026-11-02 00:00:00 UTC'));

        $this->assertCount(1, $observed);
        $this->assertEquals(new \DateTimeImmutable('2026-11-01 11:12:03 UTC'), $observed[0]->departure->estimatedAt);
        $this->assertEquals(new \DateTimeImmutable('2026-11-01 11:13:00 UTC'), $observed[0]->departure->scheduledAt);
        $this->assertEquals(new \DateTimeImmutable('2026-11-01 11:00:20 UTC'), $observed[0]->observedAt);
        $this->assertSame(DepartureStatus::Realtime, $observed[0]->departure->status);
        $this->assertSame('12@2026-11-01T11:08:00Z', $observed[0]->departure->tripKey);
        $this->assertSame('9221', $observed[0]->departure->vehicle);
    }

    #[Group('clickhouse')]
    public function testObservedIsBoundedByScheduledTimeStopsAndLines(): void
    {
        [$history] = $this->history();
        $at        = new \DateTimeImmutable('2026-11-01 11:00:00 UTC');

        $history->record('trojmiasto', $at, [
            $this->departure(null),
            $this->departure(null, stopId: '8228'),
            $this->departure(null, lineId: '172'),
            $this->departure(null, scheduled: '2026-11-01T13:13:00+01:00'),
        ]);
        $history->record('other', $at, [$this->departure(null)]);

        $from = new \DateTimeImmutable('2026-11-01 11:13:00 UTC');
        $to   = new \DateTimeImmutable('2026-11-01 12:13:00 UTC');
        $keys = fn (iterable $observed) => array_map(
            fn ($o) => $o->departure->stopId . '|' . $o->departure->lineId . '|' . $o->departure->scheduledAt->format('H:i'),
            [...$observed],
        );
        $sorted = function (array $values): array {
            sort($values);

            return $values;
        };

        // from is inclusive, to is exclusive
        $this->assertSame(['8227|171|11:13', '8227|172|11:13', '8228|171|11:13'], $sorted($keys($history->observed('trojmiasto', $from, $to))));
        $this->assertSame(['8228|171|11:13'], $keys($history->observed('trojmiasto', $from, $to, stopIds: ['8228'])));
        $this->assertSame(['8227|172|11:13'], $keys($history->observed('trojmiasto', $from, $to, stopIds: ['8227', '8228'], lineIds: ['172'])));
        $this->assertSame([], $keys($history->observed('trojmiasto', $from, $to, stopIds: ["8227' OR 1=1 --"])));
    }

    #[Group('clickhouse')]
    public function testRetentionFollowsTheConfiguration(): void
    {
        [$history, $connection] = $this->history(retentionDays: 30);

        $this->assertStringContainsString('TTL scheduled_at + toIntervalDay(30)', $this->engine($connection));

        new ClickHouseDepartureHistory($connection, TestClickHouse::migrator($connection), 90)->prepare();

        $this->assertStringContainsString('TTL scheduled_at + toIntervalDay(90)', $this->engine($connection));
    }

    public function testUnreachableServerIsADepartureHistoryException(): void
    {
        $connection = new ClickHouseConnection('http://cojedzie:cojedzie@127.0.0.1:1/cojedzie');
        $history    = new ClickHouseDepartureHistory($connection, TestClickHouse::migrator($connection), 365);

        $this->expectException(DepartureHistoryException::class);

        $history->record('trojmiasto', new \DateTimeImmutable(), [$this->departure(null)]);
    }

    /**
     * @return array{ClickHouseDepartureHistory, ClickHouseConnection}
     */
    private function history(int $retentionDays = 365): array
    {
        $connection = TestClickHouse::connection();
        $history    = new ClickHouseDepartureHistory($connection, TestClickHouse::migrator($connection), $retentionDays);
        $history->prepare();

        return [$history, $connection];
    }

    private function engine(ClickHouseConnection $connection): string
    {
        return (string) $connection->client()
            ->select("SELECT engine_full FROM system.tables WHERE database = currentDatabase() AND name = 'departures'")
            ->fetchOne('engine_full');
    }

    private function departure(?string $estimated, string $stopId = '8227', string $lineId = '171', string $scheduled = '2026-11-01T12:13:00+01:00'): LiveDeparture
    {
        return new LiveDeparture(
            stopId: $stopId,
            lineId: $lineId,
            lineSymbol: '171',
            tripKey: '12@2026-11-01T11:08:00Z',
            scheduledAt: new \DateTimeImmutable($scheduled),
            estimatedAt: $estimated !== null ? new \DateTimeImmutable($estimated) : null,
            status: $estimated !== null ? DepartureStatus::Realtime : DepartureStatus::Scheduled,
            headsign: 'Oliwa',
            vehicle: $estimated !== null ? '9221' : null,
            duty: $estimated !== null ? '171-01' : null,
        );
    }
}
