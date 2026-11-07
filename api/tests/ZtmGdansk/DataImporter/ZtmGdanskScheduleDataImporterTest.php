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
use App\Parser\Exception\StreamReadException;
use App\Provider\ZtmGdansk\DataImporter\ZtmGdanskScheduleDataImporter;
use App\Service\IdUtils;
use App\Tests\DataImport\ImporterTestCase;
use App\Tests\DataImport\StubJsonStreamer;

/**
 * The schedule is imported per line and day of the window, for the lines of this provider updated by the current
 * import, and only for the line+days not imported before.
 */
final class ZtmGdanskScheduleDataImporterTest extends ImporterTestCase
{
    private const array WINDOW = [
        '2026-10-28', '2026-10-29', '2026-10-30', '2026-10-31', '2026-11-01', '2026-11-02', '2026-11-03',
    ];
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

        $this->runImporter(
            $this->importer(fn (string $route, string $date) => $date === '2026-10-28' ? [$this->row($route, $date)] : []),
            $this->event,
        );

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
        $this->runImporter($this->importer(function (string $route) use (&$routes) {
            $routes[] = $route;

            return [];
        }), $this->event);

        $this->assertSame(['6'], array_values(array_unique($routes)));
    }

    public function testLineWithUnavailableScheduleIsSkipped(): void
    {
        $this->line(self::PROVIDER . '::6');
        $this->line(self::PROVIDER . '::8');

        $this->runImporter($this->importer(fn (string $route, string $date) => match (true) {
            $route === '6'         => throw new StreamOpenException('Cannot open'),
            $date === '2026-10-28' => [$this->row($route, $date)],
            default                => [],
        }), $this->event);

        $this->assertSame([self::PROVIDER . '::8-8-1-1-2026-10-28'], $this->connection->fetchFirstColumn('SELECT id FROM trip'));
        $this->assertCount(count(self::WINDOW), $this->failures);
    }

    public function testLineWhoseScheduleStallsIsSkipped(): void
    {
        $this->line(self::PROVIDER . '::6');
        $this->line(self::PROVIDER . '::8');

        $this->runImporter($this->importer(fn (string $route, string $date) => match (true) {
            $route === '6'         => throw new StreamReadException('Reading the stream timed out.'),
            $date === '2026-10-28' => [$this->row($route, $date)],
            default                => [],
        }), $this->event);

        $this->assertSame([self::PROVIDER . '::8-8-1-1-2026-10-28'], $this->connection->fetchFirstColumn('SELECT id FROM trip'));
        $this->assertCount(count(self::WINDOW), $this->failures);
    }

    public function testOneTaskIsSpawnedPerMissingLineDay(): void
    {
        $this->line(self::PROVIDER . '::6');
        $this->line(self::PROVIDER . '::8');
        $this->scheduleDay(self::PROVIDER . '::8', '2026-10-28');

        $requests = 0;
        $this->importer(function () use (&$requests) {
            $requests++;

            return [];
        })->import($this->reporter(), $this->event);

        $spawned = $this->event->getSpawned();
        $this->assertSame(0, $requests);
        $this->assertCount(13, $spawned);
        $this->assertSame(
            [
                'importer'    => ZtmGdanskScheduleDataImporter::class,
                'payload'     => ['line' => self::PROVIDER . '::6', 'date' => '2026-10-28'],
                'description' => '[ZTM Gdańsk] Schedule of 6 on 2026-10-28',
            ],
            $spawned[0],
        );
    }

    public function testOnlyMissingDaysAreDownloaded(): void
    {
        $this->line(self::PROVIDER . '::6');
        $this->scheduleDay(self::PROVIDER . '::6', '2026-10-28');

        $dates = [];
        $this->runImporter($this->importer(function (string $route, string $date) use (&$dates) {
            $dates[] = $date;

            return [];
        }), $this->event);

        $this->assertSame(array_slice(self::WINDOW, 1), $dates);
    }

    public function testDayWithoutTripsIsRemembered(): void
    {
        $this->line(self::PROVIDER . '::6');

        $this->runImporter($this->importer(fn () => []), $this->event);

        $this->assertSame(self::WINDOW, $this->scheduleDays());

        $requests = 0;
        $this->runImporter($this->importer(function () use (&$requests) {
            $requests++;

            return [];
        }), new DataUpdateEvent($this->event->import));

        $this->assertSame(0, $requests);
    }

    public function testSameRunOnTwoDaysGivesTwoTrips(): void
    {
        $this->line(self::PROVIDER . '::6');

        $this->runImporter($this->importer(fn (string $route, string $date) => in_array($date, ['2026-10-28', '2026-10-29'], true)
            ? [$this->row($route, $date)]
            : []), $this->event);

        $this->assertSame(
            [self::PROVIDER . '::6-6-1-1-2026-10-28', self::PROVIDER . '::6-6-1-1-2026-10-29'],
            $this->connection->fetchFirstColumn('SELECT id FROM trip ORDER BY id'),
        );
    }

    public function testTripAfterMidnightBelongsToItsServiceDate(): void
    {
        $this->line(self::PROVIDER . '::6');
        $this->connection->insert('stop', [
            'id'          => self::PROVIDER . '::101',
            'provider_id' => self::PROVIDER,
            'name'        => 'Dworzec Główny',
            'on_demand'   => 0,
        ]);

        $this->importer(fn (string $route, string $date) => [
            $this->row($route, $date, ['arrivalTime' => '1899-12-30T23:50:00', 'departureTime' => '1899-12-30T23:51:00']),
            $this->row($route, $date, ['arrivalTime' => '1899-12-31T00:29:00', 'departureTime' => '1899-12-31T00:30:00', 'stopId' => 101, 'stopSequence' => 1]),
        ])->importLineDay($this->reporter(), $this->event, self::PROVIDER . '::6', '2026-10-28');

        // Warsaw is UTC+1 after 2026-10-25
        $validity = ['valid_from' => '2026-10-28 22:50:00', 'valid_to' => '2026-10-28 23:30:00'];
        $this->assertSame([['id' => self::PROVIDER . '::6-6-1-1-2026-10-28', ...$validity]], $this->rows('trip', 'id', 'valid_from', 'valid_to'));
        $this->assertSame([$validity, $validity], $this->rows('trip_stop', 'valid_from', 'valid_to'));
    }

    public function testTripOfUnknownTrackVersionIsSkipped(): void
    {
        $this->line(self::PROVIDER . '::6');

        $this->importer(fn (string $route, string $date) => [
            $this->row($route, $date, ['topologyVersionId' => 2, 'order' => 2]),
            $this->row($route, $date),
        ])->importLineDay($this->reporter(), $this->event, self::PROVIDER . '::6', '2026-10-28');

        $this->assertSame([self::PROVIDER . '::6-6-1-1-2026-10-28'], $this->connection->fetchFirstColumn('SELECT id FROM trip'));
        $this->assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM trip_stop'));
        $this->assertCount(1, $this->warnings);
    }

    public function testImportingALineDayTwiceDoesNothing(): void
    {
        $this->line(self::PROVIDER . '::6');

        $requests = 0;
        $importer = $this->importer(function (string $route, string $date) use (&$requests) {
            $requests++;

            return [$this->row($route, $date)];
        });

        $importer->importLineDay($this->reporter(), $this->event, self::PROVIDER . '::6', '2026-10-28');
        $importer->importLineDay($this->reporter(), $this->event, self::PROVIDER . '::6', '2026-10-28');

        $this->assertSame(1, $requests);
        $this->assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM trip'));
        $this->assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM trip_stop'));
        $this->assertSame(['2026-10-28'], $this->scheduleDays());
    }

    public function testFailedDayWritesNoRow(): void
    {
        $this->line(self::PROVIDER . '::6');

        $this->runImporter($this->importer(fn (string $route, string $date) => match ($date) {
            '2026-10-28' => throw new StreamReadException('Reading the stream timed out.'),
            '2026-10-29' => [$this->row($route, $date)],
            default      => [],
        }), $this->event);

        $this->assertSame(array_slice(self::WINDOW, 1), $this->scheduleDays());
        $this->assertSame([self::PROVIDER . '::6-6-1-1-2026-10-29'], $this->connection->fetchFirstColumn('SELECT id FROM trip'));
        $this->assertSame(['Reading the stream timed out.'], $this->failures);
    }

    public function testLineDayImportedMeanwhileByAnotherRunIsNotAFailure(): void
    {
        $this->line(self::PROVIDER . '::6');

        // another run writes the day after this one checked it wasn't imported
        $this->importer(function (string $route, string $date) {
            $this->scheduleDay(self::PROVIDER . '::6', $date);

            return [];
        })->import(
            $this->reporter(),
            new DataUpdateEvent($this->event->import, ['line' => self::PROVIDER . '::6', 'date' => '2026-10-28']),
        );

        $this->assertSame([], $this->scheduleDays());
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
            'id'      => sprintf('%s::R%sT1V1', $provider, $symbol),
            'line_id' => $id,
        ]);
    }

    private function scheduleDay(string $lineId, string $date): void
    {
        $this->connection->insert('schedule_day', [
            'provider_id' => self::PROVIDER,
            'line_id'     => $lineId,
            'date'        => $date,
            'import_id'   => $this->event->import->getId(),
            'imported_at' => self::NOW,
        ], ['import_id' => 'uuid']);
    }

    /**
     * @return list<string>
     */
    private function scheduleDays(): array
    {
        return $this->connection->fetchFirstColumn('SELECT date FROM schedule_day ORDER BY date');
    }

    /**
     * A stop time of trip 1 of the route, at the stop 100 at noon.
     *
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function row(string $route, string $date, array $overrides = []): array
    {
        return [
            'routeId'           => $route,
            'busServiceName'    => $route,
            'tripId'            => 1,
            'topologyVersionId' => 1,
            'order'             => 1,
            'agencyId'          => 1,
            'noteDescription'   => null,
            'date'              => $date,
            'arrivalTime'       => '1899-12-30T12:00:00',
            'departureTime'     => '1899-12-30T12:01:00',
            'stopId'            => 100,
            'stopSequence'      => 0,
            ...$overrides,
        ];
    }

    /**
     * @param \Closure(string, string): iterable<array<string, mixed>> $schedule rows by route and date
     */
    private function importer(\Closure $schedule): ZtmGdanskScheduleDataImporter
    {
        return new ZtmGdanskScheduleDataImporter(
            $this->connection,
            new IdUtils(),
            new StubJsonStreamer(function (string $url) use ($schedule) {
                parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

                return $schedule($query['routeId'], $query['date']);
            }),
            $this->config(),
            $this->clock(),
        );
    }
}
