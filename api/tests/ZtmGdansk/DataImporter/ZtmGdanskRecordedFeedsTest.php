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
use App\Dto\Line;
use App\Event\DataUpdateEvent;
use App\Provider\ZtmGdansk\DataImporter\ZtmGdanskLineDataImporter;
use App\Provider\ZtmGdansk\DataImporter\ZtmGdanskOperatorsDataImporter;
use App\Provider\ZtmGdansk\DataImporter\ZtmGdanskScheduleDataImporter;
use App\Provider\ZtmGdansk\DataImporter\ZtmGdanskStopDataImporter;
use App\Provider\ZtmGdansk\DataImporter\ZtmGdanskTrackDataImporter;
use App\Provider\ZtmGdansk\ZtmGdanskConfig;
use App\Service\IdUtils;
use App\Service\JsonStreamer;
use App\Tests\DataImport\ImporterTestCase;

/**
 * Checks that what ZTM Gdańsk publishes still matches what the importers expect, by importing feeds recorded from
 * upstream (see Resources/2026-10-28/README.md) through the real streaming parser.
 */
final class ZtmGdanskRecordedFeedsTest extends ImporterTestCase
{
    private const string FEEDS     = __DIR__ . '/../Resources/2026-10-28';
    private const string SCHEDULES = 'recorded://stopTimes';
    private DataUpdateEvent $event;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->event = $this->newImport();
    }

    /**
     * One test, as importing the day takes a while: each step depends on the ones before.
     */
    public function testRecordedDayIsImported(): void
    {
        $this->importOperatorsStopsAndLines();

        $this->assertSame(11, $this->rowCount('operator'));
        $this->assertGreaterThan(2000, $this->rowCount('stop'));
        $this->assertSame(0, $this->rowCount('stop', "name = '' OR latitude IS NULL OR longitude IS NULL"));
        // every route of the day belongs to a known operator
        $this->assertSame(183, $this->rowCount('line'));
        $this->assertSame(
            [Line::TYPE_BUS, Line::TYPE_TRAM, Line::TYPE_TROLLEYBUS],
            $this->connection->fetchFirstColumn('SELECT DISTINCT type FROM line ORDER BY type'),
        );

        $this->tracks()->import($this->reporter(), $this->event);

        $tracks = $this->rowCount('track');
        $this->assertSame(1481, $tracks);
        // stops in trips refer to tracks of the trips feed and to stops of the stops feed
        $this->assertGreaterThan(0.95 * $tracks, $this->rowCount('track', 'final_id IS NOT NULL'));
        $this->assertGreaterThan(20000, $this->rowCount('track_stop'));

        $this->schedule()->import($this->reporter(), $this->event);

        $this->assertSame([], $this->warnings);
        $this->assertGreaterThan(0, $this->rowCount('trip_stop'));
        // trips of the schedule feed are tracks of the trips feed
        $this->assertSame(0, $this->rowCount('trip', 'track_id NOT IN (SELECT id FROM track)'));
        // the day's departures, stored in UTC, lie between 2026-10-27 23:00 and 2026-10-29 23:00 (overnight trips)
        $this->assertSame(0, $this->rowCount(
            'trip_stop',
            "departure < '2026-10-27 23:00:00' OR departure > '2026-10-29 23:00:00'",
        ));
    }

    private function importOperatorsStopsAndLines(): void
    {
        $upserter = new TableUpserter($this->connection);
        $streamer = $this->streamer();
        $ids      = new IdUtils();

        foreach ([
            new ZtmGdanskOperatorsDataImporter($this->connection, $upserter, $streamer, $ids, $this->config()),
            new ZtmGdanskStopDataImporter($this->connection, $upserter, $streamer, $ids, $this->config(), $this->clock()),
            new ZtmGdanskLineDataImporter($this->connection, $upserter, $streamer, $ids, $this->config(), $this->clock()),
        ] as $importer) {
            $importer->import($this->reporter(), $this->event);
        }
    }

    private function tracks(): ZtmGdanskTrackDataImporter
    {
        return new ZtmGdanskTrackDataImporter(
            $this->connection,
            new TableUpserter($this->connection),
            new IdUtils(),
            $this->streamer(),
            $this->config(),
            $this->clock(),
        );
    }

    private function schedule(): ZtmGdanskScheduleDataImporter
    {
        return new ZtmGdanskScheduleDataImporter($this->connection, new IdUtils(), $this->streamer(), $this->config(), $this->clock());
    }

    #[\Override]
    protected function config(): ZtmGdanskConfig
    {
        return new ZtmGdanskConfig(
            apiUrl: 'recorded:/',
            stopsUrl: self::FEEDS . '/stops.json',
            routesUrl: self::FEEDS . '/routes.json',
            tripsUrl: self::FEEDS . '/trips.json',
            stopsInTripsUrl: self::FEEDS . '/stopsintrips.json',
            agenciesUrl: self::FEEDS . '/agencies.json',
            displaysUrl: self::FEEDS . '/displays.json',
        );
    }

    /**
     * Streams the recorded files; a route's schedule comes from its own file, routes not recorded have none.
     */
    private function streamer(): JsonStreamer
    {
        return new class(self::FEEDS, self::SCHEDULES) extends JsonStreamer {
            public function __construct(
                private readonly string $feeds,
                private readonly string $schedules,
            ) {
            }

            #[\Override]
            public function stream(string $url, string $branch): iterable
            {
                if (!str_starts_with($url, $this->schedules)) {
                    return parent::stream($url, $branch);
                }

                parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
                $file = sprintf('%s/stopTimes/%s.json', $this->feeds, $query['routeId']);

                return is_file($file) ? parent::stream($file, $branch) : [];
            }
        };
    }

    private function rowCount(string $table, string $where = '1 = 1'): int
    {
        return (int) $this->connection->fetchOne(sprintf('SELECT COUNT(*) FROM %s WHERE %s', $table, $where));
    }
}
