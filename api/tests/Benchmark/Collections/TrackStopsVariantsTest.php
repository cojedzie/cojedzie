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

namespace App\Tests\Benchmark\Collections;

use App\Provider\ZtmGdansk\DataImporter\ZtmGdanskTrackDataImporter;
use App\Provider\ZtmGdansk\ZtmGdanskConfig;
use App\Service\IdUtils;
use App\Service\JsonStreamer;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class TrackStopsVariantsTest extends TestCase
{
    private const int RECORD_COUNT = 2000;

    public function testVariantsProduceIdenticalOutput(): void
    {
        $this->assertSame($this->materialize('materialized'), $this->materialize('lazy'));
    }

    public function testImporterMatchesMaterializedVariant(): void
    {
        $ids      = new IdUtils();
        $importer = new ZtmGdanskTrackDataImporter(
            $this->createStub(Connection::class),
            $ids,
            new JsonStreamer(),
            new ZtmGdanskConfig('', '', '', '', '', '', ''),
            new MockClock(),
        );
        $records = \array_slice(TrackStopsVariants::decodedRecords(), 0, self::RECORD_COUNT);

        $step = new \ReflectionMethod($importer, 'groupTrackStops');

        $result = [];
        foreach ($step->invoke($importer, $records, TrackStopsVariants::existingStopIds($ids)) as $trackId => $rows) {
            $result[$trackId] = iterator_to_array($rows);
        }

        $this->assertNotEmpty($result);
        $this->assertSame($this->materialize('materialized'), $result);
    }

    /**
     * Materializes the variant's output once, so lazy results aren't consumed twice.
     *
     * @return array<string, list<array{stop_id: string, track_id: string, sequence: int}>>
     */
    private function materialize(string $variant): array
    {
        $ids     = new IdUtils();
        $records = \array_slice(TrackStopsVariants::decodedRecords(), 0, self::RECORD_COUNT);

        $result = [];
        foreach (TrackStopsVariants::$variant($records, TrackStopsVariants::existingStopIds($ids), $ids) as $trackId => $rows) {
            $result[$trackId] = iterator_to_array($rows);
        }

        return $result;
    }
}
