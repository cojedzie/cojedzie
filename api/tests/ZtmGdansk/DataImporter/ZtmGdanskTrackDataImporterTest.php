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

use App\DataImport\TableUpserter;
use App\Event\DataUpdateEvent;
use App\Provider\ZtmGdansk\DataImporter\ZtmGdanskTrackDataImporter;
use App\Service\IdUtils;
use App\Tests\DataImport\ImporterTestCase;
use App\Tests\DataImport\StubJsonStreamer;
use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\MockClock;

/**
 * Tracks come from the stops in trips feed, which isn't ordered by track, versioned by its topology version; their
 * line and description come from the trips feed. Unless a test says otherwise, every day of the window lists the same
 * rows.
 */
final class ZtmGdanskTrackDataImporterTest extends ImporterTestCase
{
    private DataUpdateEvent $event;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->event = $this->newImport();

        $this->connection->insert('line', [
            'id'          => self::PROVIDER . '::6',
            'provider_id' => self::PROVIDER,
            'symbol'      => '6',
            'type'        => 'tram',
            'fast'        => 0,
            'night'       => 0,
        ]);

        foreach ([100, 101, 102] as $stop) {
            $this->connection->insert('stop', [
                'id'          => self::PROVIDER . '::' . $stop,
                'provider_id' => self::PROVIDER,
                'import_id'   => $this->event->import->getId(),
                'name'        => 'Stop ' . $stop,
                'on_demand'   => 0,
            ], ['import_id' => 'uuid']);
        }
    }

    public function testTracksAreInserted(): void
    {
        $this->import($this->event, [$this->trip(1, 'Oliwa (123)')], [$this->stopInTrip(1, 100, 1)]);

        $this->assertSame([
            [
                'id'          => 'trojmiasto::R6T1V1',
                'provider_id' => 'trojmiasto',
                'import_id'   => $this->event->import->getId()->toBinary(),
                'line_id'     => 'trojmiasto::6',
                'description' => 'Oliwa ',
            ],
        ], $this->tracks());
    }

    public function testTrackStopsAreOrderedAndRenumbered(): void
    {
        $this->import($this->event, [$this->trip(1), $this->trip(2)], [
            $this->stopInTrip(1, 101, 5),
            $this->stopInTrip(2, 102, 7),
            $this->stopInTrip(1, 100, 2),
            $this->stopInTrip(2, 100, 3),
            $this->stopInTrip(1, 102, 9),
        ]);

        $this->assertSame([
            ['trojmiasto::R6T1V1', 'trojmiasto::100', 0],
            ['trojmiasto::R6T1V1', 'trojmiasto::101', 1],
            ['trojmiasto::R6T1V1', 'trojmiasto::102', 2],
            ['trojmiasto::R6T2V1', 'trojmiasto::100', 0],
            ['trojmiasto::R6T2V1', 'trojmiasto::102', 1],
        ], $this->trackStops());
    }

    public function testUnknownStopsAreDroppedBeforeRenumbering(): void
    {
        $this->import($this->event, [$this->trip(1)], [
            $this->stopInTrip(1, 100, 1),
            $this->stopInTrip(1, 999, 2),
            $this->stopInTrip(1, 101, 3),
        ]);

        $this->assertSame([
            ['trojmiasto::R6T1V1', 'trojmiasto::100', 0],
            ['trojmiasto::R6T1V1', 'trojmiasto::101', 1],
        ], $this->trackStops());
    }

    public function testFinalStopIsTheLastStop(): void
    {
        $this->import($this->event, [$this->trip(1)], [
            $this->stopInTrip(1, 102, 3),
            $this->stopInTrip(1, 100, 1),
        ]);

        $this->assertSame(
            'trojmiasto::102',
            $this->connection->fetchOne('SELECT ts.stop_id FROM track t JOIN track_stop ts ON ts.id = t.final_id'),
        );
    }

    public function testTrackWithOnlyUnknownStopsHasNoFinalStop(): void
    {
        $this->import($this->event, [$this->trip(1)], [$this->stopInTrip(1, 999, 1)]);

        $this->assertSame([], $this->trackStops());
        $this->assertNull($this->connection->fetchOne('SELECT final_id FROM track'));
    }

    public function testReimportReplacesTrackStops(): void
    {
        $this->import($this->event, [$this->trip(1)], [
            $this->stopInTrip(1, 100, 1),
            $this->stopInTrip(1, 101, 2),
            $this->stopInTrip(1, 102, 3),
        ]);

        $this->import($this->nextImport(), [$this->trip(1)], [
            $this->stopInTrip(1, 102, 1),
            $this->stopInTrip(1, 100, 2),
        ]);

        $this->assertSame([
            ['trojmiasto::R6T1V1', 'trojmiasto::102', 0],
            ['trojmiasto::R6T1V1', 'trojmiasto::100', 1],
        ], $this->trackStops());
    }

    public function testReimportWithOnlyUnknownStopsClearsTrackStops(): void
    {
        $this->import($this->event, [$this->trip(1)], [$this->stopInTrip(1, 100, 1)]);

        $this->import($this->nextImport(), [$this->trip(1)], [$this->stopInTrip(1, 999, 1)]);

        $this->assertSame([], $this->trackStops());
    }

    public function testTwoVersionsOfARouteCoexist(): void
    {
        $this->importDays($this->event, [
            '2026-10-28' => [[$this->trip(1)], [
                $this->stopInTrip(1, 100, 1, version: 100),
                $this->stopInTrip(1, 101, 2, version: 100),
            ]],
            '2026-10-29' => [[$this->trip(1)], [
                $this->stopInTrip(1, 100, 1, version: 101),
                $this->stopInTrip(1, 102, 2, version: 101),
            ]],
        ]);

        $this->assertSame([
            ['id' => 'trojmiasto::R6T1V100', 'valid_from' => '2026-10-27 23:00:00', 'valid_to' => '2026-10-28 22:59:59'],
            ['id' => 'trojmiasto::R6T1V101', 'valid_from' => '2026-10-28 23:00:00', 'valid_to' => '2026-10-29 22:59:59'],
        ], $this->rows('track', 'id', 'valid_from', 'valid_to'));

        $this->assertSame([
            ['trojmiasto::R6T1V100', 'trojmiasto::100', 0],
            ['trojmiasto::R6T1V100', 'trojmiasto::101', 1],
            ['trojmiasto::R6T1V101', 'trojmiasto::100', 0],
            ['trojmiasto::R6T1V101', 'trojmiasto::102', 1],
        ], $this->trackStops());
    }

    public function testTrackListedOnSeveralDaysIsValidForAllOfThem(): void
    {
        $this->importDays($this->event, [
            '2026-10-28' => [[$this->trip(1)], [$this->stopInTrip(1, 100, 1)]],
            '2026-10-29' => [[$this->trip(1)], [$this->stopInTrip(1, 100, 1)]],
            '2026-10-30' => [[$this->trip(1)], [$this->stopInTrip(1, 100, 1)]],
        ]);

        $this->assertSame([
            ['id' => 'trojmiasto::R6T1V1', 'valid_from' => '2026-10-27 23:00:00', 'valid_to' => '2026-10-30 22:59:59'],
        ], $this->rows('track', 'id', 'valid_from', 'valid_to'));
        $this->assertSame([['trojmiasto::R6T1V1', 'trojmiasto::100', 0]], $this->trackStops());
    }

    public function testLaterImportExtendsValidToAndKeepsValidFrom(): void
    {
        $this->importDays($this->event, ['2026-10-28' => [[$this->trip(1)], [$this->stopInTrip(1, 100, 1)]]]);

        $this->importDays($this->nextImport(), [
            '2026-10-29' => [[$this->trip(1)], [$this->stopInTrip(1, 100, 1)]],
        ], new MockClock('2026-10-29 12:00:00'));

        $this->assertSame(
            [['valid_from' => '2026-10-27 23:00:00', 'valid_to' => '2026-10-29 22:59:59']],
            $this->rows('track', 'valid_from', 'valid_to'),
        );
    }

    public function testTrackStopsShareTheTracksValidity(): void
    {
        $this->importDays($this->event, [
            '2026-10-28' => [[$this->trip(1)], [$this->stopInTrip(1, 100, 1), $this->stopInTrip(1, 101, 2)]],
            '2026-10-29' => [[$this->trip(1)], [$this->stopInTrip(1, 100, 1), $this->stopInTrip(1, 101, 2)]],
        ]);

        $this->assertSame([
            ['stop_id' => 'trojmiasto::100', 'valid_from' => '2026-10-27 23:00:00', 'valid_to' => '2026-10-29 22:59:59'],
            ['stop_id' => 'trojmiasto::101', 'valid_from' => '2026-10-27 23:00:00', 'valid_to' => '2026-10-29 22:59:59'],
        ], $this->rows('track_stop', 'stop_id', 'valid_from', 'valid_to'));
    }

    public function testVersionSupersededForTheWholeWindowExpiresNow(): void
    {
        $this->import($this->event, [$this->trip(1), $this->trip(2)], [
            $this->stopInTrip(1, 100, 1, version: 100),
            $this->stopInTrip(2, 100, 1, version: 100),
        ]);

        // ZTM labels every day with the new version; trip 2 is missing from this feed and keeps its validity
        $this->import($this->nextImport(), [$this->trip(1)], [$this->stopInTrip(1, 100, 1, version: 101)]);

        // the version 100 of trip 1 ends at the time of the second import, the clock being in UTC
        $this->assertSame([
            ['id' => 'trojmiasto::R6T1V100', 'valid_to' => self::NOW],
            ['id' => 'trojmiasto::R6T1V101', 'valid_to' => '2026-11-03 22:59:59'],
            ['id' => 'trojmiasto::R6T2V100', 'valid_to' => '2026-11-03 22:59:59'],
        ], $this->rows('track', 'id', 'valid_to'));
        $this->assertSame([
            ['track_id' => 'trojmiasto::R6T1V100', 'valid_to' => self::NOW],
            ['track_id' => 'trojmiasto::R6T1V101', 'valid_to' => '2026-11-03 22:59:59'],
            ['track_id' => 'trojmiasto::R6T2V100', 'valid_to' => '2026-11-03 22:59:59'],
        ], $this->rows('track_stop', 'track_id', 'valid_to'));
    }

    public function testTrackWithoutATripIsSkipped(): void
    {
        $this->import($this->event, [$this->trip(1)], [$this->stopInTrip(1, 100, 1), $this->stopInTrip(2, 100, 1)]);

        $this->assertSame(['trojmiasto::R6T1V1'], $this->connection->fetchFirstColumn('SELECT id FROM track'));
        $this->assertSame([['trojmiasto::R6T1V1', 'trojmiasto::100', 0]], $this->trackStops());
    }

    public function testSkippedTracksAreReportedPerDay(): void
    {
        $this->importDays($this->event, [
            '2026-10-28' => [[$this->trip(1)], [$this->stopInTrip(1, 100, 1), $this->stopInTrip(2, 100, 1)]],
            // a whole day missing from the trips feed
            '2026-10-29' => [[], [$this->stopInTrip(2, 100, 1), $this->stopInTrip(3, 100, 1)]],
        ]);

        $this->assertSame([
            'Skipped 1 tracks of 2026-10-28 without a trip in the trips feed',
            'Skipped 2 tracks of 2026-10-29 without a trip in the trips feed',
        ], $this->warnings);
    }

    public function testProgressIsReportedPerDayOfTheWindow(): void
    {
        $this->importDays($this->event, [
            '2026-10-28' => [[$this->trip(1)], [$this->stopInTrip(1, 100, 1)]],
            '2026-10-29' => [[$this->trip(1)], [$this->stopInTrip(1, 100, 1)]],
            '2026-10-30' => [[$this->trip(1)], [$this->stopInTrip(1, 100, 1)]],
        ]);

        // one call per day present in the feed and the final one, all with the total
        $this->assertSame(array_fill(0, 4, 7.0), $this->progressTotals);
    }

    /**
     * Starts the next import, with the stops imported again by it, as the stop importer runs before this one.
     */
    private function nextImport(): DataUpdateEvent
    {
        $event = $this->newImport();

        $this->connection->update('stop', ['import_id' => $event->import->getId()], [], ['import_id' => 'uuid']);

        return $event;
    }

    /**
     * Imports with every day of the window listing the same trips and stops in trips.
     */
    private function import(DataUpdateEvent $event, array $trips, array $stopsInTrips): void
    {
        $config = $this->config();

        $this->importFrom($event, new StubJsonStreamer(fn (string $url) => $url === $config->tripsUrl ? $trips : $stopsInTrips));
    }

    /**
     * Imports with only the given days listing anything.
     *
     * @param array<string, array{list<array>, list<array>}> $days trips and stops in trips, by date
     */
    private function importDays(DataUpdateEvent $event, array $days, ?ClockInterface $clock = null): void
    {
        $config = $this->config();

        $this->importFrom($event, StubJsonStreamer::byDate(function (string $url, string $date) use ($config, $days) {
            [$trips, $stopsInTrips] = $days[$date] ?? [[], []];

            return $url === $config->tripsUrl ? $trips : $stopsInTrips;
        }), $clock);
    }

    private function importFrom(DataUpdateEvent $event, StubJsonStreamer $streamer, ?ClockInterface $clock = null): void
    {
        new ZtmGdanskTrackDataImporter(
            $this->connection,
            new TableUpserter($this->connection),
            new IdUtils(),
            $streamer,
            $this->config(),
            $clock ?? $this->clock(),
        )->import($this->reporter(), $event);
    }

    private function trip(int $trip, string $headsign = 'Oliwa'): array
    {
        return [
            'id'           => sprintf('R6T%d', $trip),
            'routeId'      => 6,
            'tripHeadsign' => $headsign,
        ];
    }

    private function stopInTrip(int $trip, int $stop, int $sequence, int $version = 1): array
    {
        return [
            'routeId'           => 6,
            'tripId'            => $trip,
            'stopId'            => $stop,
            'stopSequence'      => $sequence,
            'topologyVersionId' => $version,
        ];
    }

    private function tracks(): array
    {
        return $this->rows('track', 'id', 'provider_id', 'import_id', 'line_id', 'description');
    }

    /**
     * @return list<array{string, string, int}> track, stop and sequence, in the order of the track and sequence
     */
    private function trackStops(): array
    {
        return $this->connection->fetchAllNumeric('SELECT track_id, stop_id, sequence FROM track_stop ORDER BY track_id, sequence');
    }
}
