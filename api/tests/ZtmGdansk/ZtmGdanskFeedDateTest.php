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

namespace App\Tests\ZtmGdansk;

use App\Provider\ZtmGdansk\ZtmGdanskProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * ZTM Gdańsk feeds are keyed by Warsaw dates, so the day to import turns over at midnight in Warsaw, not in UTC.
 */
final class ZtmGdanskFeedDateTest extends TestCase
{
    public static function moments(): iterable
    {
        yield 'winter, before midnight in Warsaw' => ['2026-10-28 22:30', '2026-10-28'];
        yield 'winter, after midnight in Warsaw' => ['2026-10-28 23:30', '2026-10-29'];
        yield 'summer, after midnight in Warsaw' => ['2026-07-01 22:30', '2026-07-02'];
    }

    /**
     * @dataProvider moments
     */
    public function testFeedDateIsTodayInWarsaw(string $utc, string $expected): void
    {
        $clock = new MockClock($utc, 'UTC');

        $this->assertSame($expected, ZtmGdanskProvider::feedDate($clock));
    }
}
