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

use App\Service\IdUtils;
use Kadet\Functional\Collections as c;
use PHPUnit\Framework\TestCase;

final class TrackStopsVariantsTest extends TestCase
{
    private const int RECORD_COUNT = 2000;

    public function testVariantsProduceIdenticalOutput(): void
    {
        $current = $this->materialize('current');

        $this->assertSame($current, $this->materialize('materialized'));
        $this->assertSame($current, $this->materialize('lazy'));
    }

    public function testFilterDropsRows(): void
    {
        $rows = array_sum(array_map(count(...), $this->materialize('current')));

        $this->assertLessThan(self::RECORD_COUNT, $rows);
    }

    /**
     * The import benchmark streams the records and the pipeline benchmark decodes them, so they must be equal.
     * Only the head is streamed, the whole branch takes seconds. The streamer gives numbers as floats, hence
     * assertEquals().
     */
    public function testDecodedRecordsMatchStreamedRecords(): void
    {
        $decoded = TrackStopsVariants::decodedRecords();

        $this->assertCount(21897, $decoded);
        $this->assertEquals(
            TrackStopsVariants::records() |> c\take(self::RECORD_COUNT) |> c\toArray(),
            \array_slice($decoded, 0, self::RECORD_COUNT)
        );
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
