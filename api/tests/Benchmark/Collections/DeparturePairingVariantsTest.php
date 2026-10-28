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

use App\Dto\Departure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DeparturePairingVariantsTest extends TestCase
{
    public static function sizes(): iterable
    {
        yield 'production' => [32, 16];
        yield 'stress' => [512, 256];
    }

    #[DataProvider('sizes')]
    public function testVariantsProduceIdenticalOutput(int $scheduled, int $realtime): void
    {
        $variants          = new DeparturePairingVariants();
        [$schedule, $real] = DeparturePairingVariants::fixture($scheduled, $realtime);

        $materialized = $this->describe($variants->materialized($schedule, $real));

        $this->assertCount($scheduled + $realtime - (int) round($realtime * 0.7), $materialized);
        $this->assertSame($materialized, $this->describe($variants->lazy($schedule, $real)));
    }

    #[DataProvider('sizes')]
    public function testProductionPairMatchesMaterializedVariant(int $scheduled, int $realtime): void
    {
        $variants          = new DeparturePairingVariants();
        [$schedule, $real] = DeparturePairingVariants::fixture($scheduled, $realtime);

        $this->assertSame(
            $this->describe($variants->production($schedule, $real)),
            $this->describe($variants->materialized($schedule, $real))
        );
    }

    /**
     * @param list<Departure> $departures
     *
     * @return list<array{string, string, ?string, ?string}>
     */
    private function describe(array $departures): array
    {
        return array_map(fn (Departure $departure) => [
            $departure->getLine()->getId(),
            $departure->getScheduled()->format('H:i'),
            $departure->getEstimated()?->format('H:i'),
            $departure->getDisplay(),
        ], $departures);
    }
}
