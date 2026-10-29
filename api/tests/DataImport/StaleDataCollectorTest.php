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

use App\DataImport\StaleDataCollector;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Uid\Uuid;

final class StaleDataCollectorTest extends ImporterTestCase
{
    public function testRemovesRowsNotImportedWithinTheRetention(): void
    {
        $old   = $this->import('-10 days');
        $fresh = $this->import('-1 day');

        $this->row('stop', 'gone', $old);
        $this->row('stop', 'kept', $fresh);

        $this->collect();

        $this->assertSame(['kept'], $this->ids('stop'));
    }

    public function testRetentionIsConfigurable(): void
    {
        $this->import('-1 day');
        $this->row('stop', 'five-days-old', $this->import('-5 days'));

        $this->collect('7 days');
        $this->assertSame(['five-days-old'], $this->ids('stop'));

        $this->collect('3 days');
        $this->assertSame([], $this->ids('stop'));
    }

    public function testRemovesAStaleLineWithItsTracksAndTrips(): void
    {
        $old = $this->import('-10 days');
        $this->import('-1 day');

        $this->row('operator', 'operator', $old);
        $this->row('line', 'line', $old, ['operator_id' => 'operator']);
        $this->row('stop', 'stop', $old);
        $this->row('track', 'track', $old, ['line_id' => 'line']);
        $this->row('track_stop', null, $old, ['track_id' => 'track', 'stop_id' => 'stop']);
        $this->row('trip', 'trip', $old, ['track_id' => 'track', 'operator_id' => 'operator']);
        $this->row('trip_stop', null, $old, ['trip_id' => 'trip', 'stop_id' => 'stop']);

        $this->collect();

        foreach (['operator', 'line', 'stop', 'track', 'track_stop', 'trip', 'trip_stop'] as $table) {
            $this->assertSame(0, $this->countRows($table), $table);
        }
    }

    public function testKeepsStaleRowsThatFreshRowsStillUse(): void
    {
        $old   = $this->import('-10 days');
        $fresh = $this->import('-1 day');

        $this->row('operator', 'operator', $old);
        $this->row('stop', 'stop', $old);
        $this->row('line', 'line', $fresh, ['operator_id' => 'operator']);
        $this->row('track', 'track', $fresh, ['line_id' => 'line']);
        $this->row('track_stop', null, $fresh, ['track_id' => 'track', 'stop_id' => 'stop']);

        $this->collect();

        $this->assertSame(['operator'], $this->ids('operator'));
        $this->assertSame(['stop'], $this->ids('stop'));
        $this->assertSame(1, $this->countRows('track_stop'));
    }

    public function testDoesNothingWithoutARecentFinishedImport(): void
    {
        $old = $this->import('-10 days');
        $this->import('-1 day', finished: false);

        $this->row('stop', 'stop', $old);

        $this->assertSame([], $this->collect());
        $this->assertSame(['stop'], $this->ids('stop'));
    }

    public function testRemovesOldImportsNothingRefersTo(): void
    {
        $unused = $this->import('-10 days');
        $used   = $this->import('-9 days');
        $fresh  = $this->import('-1 day');

        $this->row('stop', 'stop', $fresh);
        $this->connection->update('provider', ['import_id' => $used], ['id' => self::PROVIDER], ['import_id' => 'uuid']);

        $this->collect();

        $imports = array_map(
            fn ($id) => Type::getType('uuid')->convertToPHPValue($id, $this->connection->getDatabasePlatform())->toRfc4122(),
            $this->connection->fetchFirstColumn('SELECT id FROM import'),
        );
        $this->assertNotContains($unused->toRfc4122(), $imports);
        $this->assertContains($used->toRfc4122(), $imports);
        $this->assertContains($fresh->toRfc4122(), $imports);
    }

    /**
     * @return array<string, int>
     */
    private function collect(string $retention = '7 days'): array
    {
        return new StaleDataCollector($this->connection, new MockClock(self::NOW), $retention)->collect();
    }

    private function import(string $started, bool $finished = true): Uuid
    {
        $id = Uuid::v4();
        $at = new \DateTimeImmutable(self::NOW)->modify($started);

        $this->connection->insert(
            'import',
            [
                'id'          => $id,
                'started_at'  => $at,
                'finished_at' => $finished ? $at->modify('+10 minutes') : null,
            ],
            [
                'id'          => 'uuid',
                'started_at'  => 'datetime_immutable',
                'finished_at' => 'datetime_immutable',
            ],
        );

        return $id;
    }

    /**
     * @param array<string, string> $columns
     */
    private function row(string $table, ?string $id, Uuid $import, array $columns = []): void
    {
        $required = match ($table) {
            'operator'   => ['name' => 'Operator'],
            'line'       => ['symbol' => '6', 'type' => 'tram', 'fast' => 0, 'night' => 0],
            'stop'       => ['name' => 'Stop', 'on_demand' => 0],
            'track_stop' => ['sequence' => 1],
            'trip_stop'  => ['sequence' => 1, 'arrival' => self::NOW, 'departure' => self::NOW],
            default      => [],
        };
        $provider = in_array($table, ['track_stop', 'trip_stop'], true) ? [] : ['provider_id' => self::PROVIDER];

        $this->connection->insert(
            $table,
            [...($id === null ? [] : ['id' => $id]),
                ...$provider,
                ...$required,
                ...$columns,
                'import_id' => $import],
            ['import_id' => 'uuid'],
        );
    }

    /**
     * @return list<string>
     */
    private function ids(string $table): array
    {
        return $this->connection->fetchFirstColumn(sprintf('SELECT id FROM %s ORDER BY id', $table));
    }

    private function countRows(string $table): int
    {
        return (int) $this->connection->fetchOne(sprintf('SELECT COUNT(*) FROM %s', $table));
    }
}
