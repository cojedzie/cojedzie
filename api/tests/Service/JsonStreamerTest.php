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
    private const string FEED           = __DIR__ . '/Resources/dated-feed.json';
    private const string FORMATTED_FEED = __DIR__ . '/Resources/dated-feed-formatted.json';
    private const string MALFORMED_FEED = __DIR__ . '/Resources/dated-feed-malformed.json';

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

    public function testDaysCanBeSkippedWithoutReadingTheirRows(): void
    {
        $days = new JsonStreamer()->streamDated(self::FEED, 'trips', ['2026-10-28', '2026-10-29']);

        $this->assertSame(['2026-10-28', '2026-10-29'], array_keys(iterator_to_array($days)));
    }

    public function testReadsDaysSplitAcrossReads(): void
    {
        $result = $this->collect(['2026-10-28', '2026-10-29', '2026-10-30'], readSize: 7);

        $this->assertSame([
            '2026-10-28' => [['id' => 'T1'], ['id' => 'T2']],
            '2026-10-29' => [['id' => 'T3']],
            '2026-10-30' => [['id' => 'T4'], ['id' => 'T5']],
        ], $result);
    }

    public function testReadsFormattedFeedsWithDatesInValues(): void
    {
        $result = $this->collect(['2026-10-28', '2026-10-29'], feed: self::FORMATTED_FEED);

        $this->assertSame([
            '2026-10-28' => [['id' => 'T1', 'date' => '2026-10-29']],
            '2026-10-29' => [['id' => 'T2', 'date' => '2026-10-28']],
        ], $result);
    }

    public function testMissingFeedCannotBeOpened(): void
    {
        $this->expectException(StreamOpenException::class);

        $this->collect(['2026-10-28'], feed: self::FEED . '-missing');
    }

    public function testMalformedFeedCannotBeRead(): void
    {
        $this->expectException(StreamReadException::class);

        $this->collect(['2026-10-28'], feed: self::MALFORMED_FEED);
    }

    /**
     * @return array<string, array> trips of the requested dates of the feed, by date
     */
    private function collect(array $dates, string $feed = self::FEED, int $readSize = 1 << 20): array
    {
        $result = [];
        foreach (new JsonStreamer($readSize)->streamDated($feed, 'trips', $dates) as $date => $rows) {
            $result[$date] = iterator_to_array($rows, false);
        }

        return $result;
    }
}
