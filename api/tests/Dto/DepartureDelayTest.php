<?php
/*
 * Copyright (C) 2021 Kacper Donat
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

namespace App\Tests\Dto;

use App\Dto\Departure;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

final class DepartureDelayTest extends TestCase
{
    public static function delays(): iterable
    {
        yield 'late' => ['2026-10-25 12:05:00', '2026-10-25 12:07:00', 120];
        yield 'early' => ['2026-10-25 12:05:00', '2026-10-25 12:04:00', -60];
        yield 'on time' => ['2026-10-25 12:05:00', '2026-10-25 12:05:00', 0];
        yield 'sub-second part is dropped' => ['2026-10-25 12:05:00', '2026-10-25 12:07:00.600', 120];
    }

    /**
     * @dataProvider delays
     */
    public function testDelayIsSignedWholeSeconds(string $scheduled, string $estimated, int $expected): void
    {
        $departure = new Departure();
        $departure->setScheduled(new Carbon($scheduled));
        $departure->setEstimated(new Carbon($estimated));

        $this->assertSame($expected, $departure->getDelay());
    }

    public function testDelayIsNullWithoutEstimate(): void
    {
        $departure = new Departure();
        $departure->setScheduled(new Carbon('2026-10-25 12:05:00'));

        $this->assertNull($departure->getDelay());
    }
}
