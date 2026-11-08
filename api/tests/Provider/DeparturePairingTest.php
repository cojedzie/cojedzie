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

namespace App\Tests\Provider;

use App\Provider\DeparturePairing;
use PHPUnit\Framework\TestCase;

final class DeparturePairingTest extends TestCase
{
    public function testKeyIsTheUtcMinuteOfTheDay(): void
    {
        $this->assertSame(
            DeparturePairing::key('S1', 'L1', new \DateTimeImmutable('2026-01-01 11:05:00Z')),
            DeparturePairing::key('S1', 'L1', new \DateTimeImmutable('2026-01-01 12:05:40+01:00')),
        );
        $this->assertNotSame(
            DeparturePairing::key('S1', 'L1', new \DateTimeImmutable('2026-01-01 11:05:00Z')),
            DeparturePairing::key('S1', 'L1', new \DateTimeImmutable('2026-01-02 11:05:00Z')),
        );
    }

    public function testUnmatchedLiveDeparturesComeLast(): void
    {
        $key = fn (string $value) => $value;

        $pairs = DeparturePairing::pair(['a', 'b', 'c'], ['x', 'c', 'a', 'a'], $key, $key)->toArray();

        $this->assertSame([
            ['planned' => 'a', 'live' => 'a'],
            ['planned' => 'b', 'live' => null],
            ['planned' => 'c', 'live' => 'c'],
            ['planned' => null, 'live' => 'x'],
        ], $pairs);
    }

    public function testTheFirstLiveDepartureOfAKeyWins(): void
    {
        $key = fn (array $row) => $row[0];

        $pairs = DeparturePairing::pair([['a', 'planned']], [['a', 'first'], ['a', 'second']], $key, $key)->toArray();

        $this->assertSame([['planned' => ['a', 'planned'], 'live' => ['a', 'first']]], $pairs);
    }
}
