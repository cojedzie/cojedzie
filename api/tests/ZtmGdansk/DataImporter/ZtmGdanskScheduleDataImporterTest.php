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

namespace App\Tests\ZtmGdansk\DataImporter;

use App\Event\DataUpdateEvent;
use App\Parser\Exception\StreamOpenException;
use App\Provider\ZtmGdansk\DataImporter\ZtmGdanskScheduleDataImporter;
use App\Service\IdUtils;
use App\Tests\DataImport\ImporterTestCase;
use App\Tests\DataImport\StubJsonStreamer;

/**
 * The schedule is imported line by line, for the lines of this provider updated by the current import.
 */
final class ZtmGdanskScheduleDataImporterTest extends ImporterTestCase
{
    private DataUpdateEvent $event;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->event = $this->newImport();

        $this->connection->insert('provider', [
            'id'          => 'other',
            'name'        => 'Other',
            'class'       => 'Other',
            'update_date' => self::NOW,
        ]);
        $this->connection->insert('operator', [
            'id'          => self::PROVIDER . '::1',
            'provider_id' => self::PROVIDER,
            'name'        => 'ZTM',
        ]);
        $this->connection->insert('stop', [
            'id'          => self::PROVIDER . '::100',
            'provider_id' => self::PROVIDER,
            'name'        => 'Brama Wyżynna',
            'on_demand'   => 0,
        ]);
    }

    public function testTripStopsAreStampedWithTheImport(): void
    {
        $this->line(self::PROVIDER . '::6');

        $this->importer(fn (string $route) => [$this->row($route)])->import($this->reporter(), $this->event);

        $this->assertSame(
            [$this->event->import->getId()->toBinary()],
            $this->connection->fetchFirstColumn('SELECT import_id FROM trip_stop'),
        );
    }

    public function testOnlyLinesOfThisProviderAreImported(): void
    {
        $this->line(self::PROVIDER . '::6');
        $this->line('other::7');

        $routes = [];
        $this->importer(function (string $route) use (&$routes) {
            $routes[] = $route;

            return [];
        })->import($this->reporter(), $this->event);

        $this->assertSame(['6'], $routes);
    }

    public function testLineWithUnavailableScheduleIsSkipped(): void
    {
        $this->line(self::PROVIDER . '::6');
        $this->line(self::PROVIDER . '::8');

        $this->importer(fn (string $route) => $route === '6'
            ? throw new StreamOpenException('Cannot open')
            : [$this->row($route)])->import($this->reporter(), $this->event);

        $this->assertSame([self::PROVIDER . '::8-8-1-1'], $this->connection->fetchFirstColumn('SELECT id FROM trip'));
        $this->assertCount(1, $this->warnings);
    }

    public function testProgressTotalIsTheNumberOfLines(): void
    {
        $this->line(self::PROVIDER . '::6');
        $this->line(self::PROVIDER . '::8');

        $this->importer(fn () => [])->import($this->reporter(), $this->event);

        $this->assertSame([2.0, 2.0], $this->progressTotals);
    }

    private function line(string $id): void
    {
        [$provider, $symbol] = explode('::', $id);

        $this->connection->insert('line', [
            'id'          => $id,
            'provider_id' => $provider,
            'import_id'   => $this->event->import->getId(),
            'symbol'      => $symbol,
            'type'        => 'bus',
            'fast'        => 0,
            'night'       => 0,
        ], ['import_id' => 'uuid']);

        $this->connection->insert('track', [
            'id'      => sprintf('%s::R%sT1', $provider, $symbol),
            'line_id' => $id,
        ]);
    }

    private function row(string $route): array
    {
        return [
            'routeId'         => $route,
            'busServiceName'  => $route,
            'tripId'          => 1,
            'order'           => 1,
            'agencyId'        => 1,
            'noteDescription' => null,
            'date'            => '2026-10-28',
            'arrivalTime'     => '1899-12-30T12:00:00',
            'departureTime'   => '1899-12-30T12:01:00',
            'stopId'          => 100,
            'stopSequence'    => 0,
        ];
    }

    /**
     * @param \Closure(string): iterable<array<string, mixed>> $schedule
     */
    private function importer(\Closure $schedule): ZtmGdanskScheduleDataImporter
    {
        return new ZtmGdanskScheduleDataImporter(
            $this->connection,
            new IdUtils(),
            new StubJsonStreamer(function (string $url) use ($schedule) {
                parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

                return $schedule($query['routeId']);
            }),
            $this->config(),
            $this->clock(),
        );
    }
}
