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
use App\Provider\ZtmGdansk\DataImporter\ZtmGdanskTrackDataImporter;
use App\Service\IdUtils;
use App\Tests\DataImport\ImporterTestCase;
use App\Tests\DataImport\StubJsonStreamer;

/**
 * Tracks come from the trips feed, their stops from the stops in trips feed, which isn't ordered by track.
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
        $this->import($this->event, [$this->trip(1, 'Oliwa (123)')], []);

        $this->assertSame([
            [
                'id'          => 'trojmiasto::R6T1',
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
            ['trojmiasto::R6T1', 'trojmiasto::100', 0],
            ['trojmiasto::R6T1', 'trojmiasto::101', 1],
            ['trojmiasto::R6T1', 'trojmiasto::102', 2],
            ['trojmiasto::R6T2', 'trojmiasto::100', 0],
            ['trojmiasto::R6T2', 'trojmiasto::102', 1],
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
            ['trojmiasto::R6T1', 'trojmiasto::100', 0],
            ['trojmiasto::R6T1', 'trojmiasto::101', 1],
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
            ['trojmiasto::R6T1', 'trojmiasto::102', 0],
            ['trojmiasto::R6T1', 'trojmiasto::100', 1],
        ], $this->trackStops());
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

    private function import(DataUpdateEvent $event, array $trips, array $stopsInTrips): void
    {
        $config = $this->config();

        new ZtmGdanskTrackDataImporter(
            $this->connection,
            new IdUtils(),
            new StubJsonStreamer(fn (string $url) => $url === $config->tripsUrl ? $trips : $stopsInTrips),
            $config,
            $this->clock(),
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

    private function stopInTrip(int $trip, int $stop, int $sequence): array
    {
        return [
            'routeId'      => 6,
            'tripId'       => $trip,
            'stopId'       => $stop,
            'stopSequence' => $sequence,
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
