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

namespace App\Tests\Service;

use App\Parser\Exception\StreamOpenException;
use App\Parser\Exception\StreamReadException;
use App\Service\JsonStreamer;
use PHPUnit\Framework\TestCase;

final class JsonStreamerTest extends TestCase
{
    private const array FEED = [
        '2026-10-28' => [
            'lastUpdate' => '2026-10-28 03:00:00',
            'trips'      => [['id' => 'T1'], ['id' => 'T2']],
        ],
        '2026-10-29' => ['lastUpdate' => '2026-10-29 03:00:00', 'trips' => [['id' => 'T3']]],
        '2026-10-30' => ['lastUpdate' => '2026-10-30 03:00:00', 'trips' => [['id' => 'T4'], ['id' => 'T5']]],
    ];
    private string $file;

    #[\Override]
    protected function setUp(): void
    {
        $this->file = tempnam(sys_get_temp_dir(), 'feed');
        file_put_contents($this->file, json_encode(self::FEED));
    }

    #[\Override]
    protected function tearDown(): void
    {
        @unlink($this->file);
    }

    public function testYieldsRowsOfEachRequestedDate(): void
    {
        $result = $this->collect(['2026-10-28', '2026-10-30']);

        $this->assertSame([
            '2026-10-28' => [['id' => 'T1'], ['id' => 'T2']],
            '2026-10-30' => [['id' => 'T4'], ['id' => 'T5']],
        ], $result);
    }

    public function testSkipsDatesMissingFromTheFile(): void
    {
        $result = $this->collect(['2026-10-29', '2026-12-01']);

        $this->assertSame(['2026-10-29' => [['id' => 'T3']]], $result);
    }

    public function testIgnoresLastUpdate(): void
    {
        $result = $this->collect(['2026-10-28', '2026-10-29', '2026-10-30']);

        foreach ($result as $rows) {
            foreach ($rows as $row) {
                $this->assertIsArray($row);
            }
        }
        $this->assertCount(3, $result);
    }

    public function testDoesNotRequireConsumingEveryDate(): void
    {
        $dates = [];
        foreach (new JsonStreamer()->streamDated($this->file, 'trips', ['2026-10-28', '2026-10-29']) as $date => $rows) {
            $dates[] = $date;
        }

        $this->assertSame(['2026-10-28', '2026-10-29'], $dates);
    }

    public function testReadsDaysSplitAcrossReads(): void
    {
        $result = [];
        foreach (new JsonStreamer(readSize: 7)->streamDated($this->file, 'trips', ['2026-10-28', '2026-10-29', '2026-10-30']) as $date => $rows) {
            $result[$date] = iterator_to_array($rows, false);
        }

        $this->assertSame([
            '2026-10-28' => [['id' => 'T1'], ['id' => 'T2']],
            '2026-10-29' => [['id' => 'T3']],
            '2026-10-30' => [['id' => 'T4'], ['id' => 'T5']],
        ], $result);
    }

    public function testReadsFormattedFeedsWithDatesInValues(): void
    {
        file_put_contents($this->file, json_encode([
            '2026-10-28' => ['lastUpdate' => '2026-10-28 03:00:00', 'trips' => [['id' => 'T1', 'date' => '2026-10-29']]],
            '2026-10-29' => ['lastUpdate' => '2026-10-29 03:00:00', 'trips' => [['id' => 'T2', 'date' => '2026-10-28']]],
        ], JSON_PRETTY_PRINT));

        $this->assertSame([
            '2026-10-28' => [['id' => 'T1', 'date' => '2026-10-29']],
            '2026-10-29' => [['id' => 'T2', 'date' => '2026-10-28']],
        ], $this->collect(['2026-10-28', '2026-10-29']));
    }

    public function testMissingFeedCannotBeOpened(): void
    {
        $this->expectException(StreamOpenException::class);

        iterator_to_array(new JsonStreamer()->streamDated($this->file . '-missing', 'trips', ['2026-10-28']));
    }

    public function testMalformedFeedCannotBeRead(): void
    {
        file_put_contents($this->file, '{"2026-10-28": {"trips": [');

        $this->expectException(StreamReadException::class);

        $this->collect(['2026-10-28']);
    }

    private function collect(array $dates): array
    {
        $result = [];
        foreach (new JsonStreamer()->streamDated($this->file, 'trips', $dates) as $date => $rows) {
            $result[$date] = iterator_to_array($rows, false);
        }

        return $result;
    }
}
