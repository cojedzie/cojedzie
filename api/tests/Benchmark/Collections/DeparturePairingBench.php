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
use App\Dto\ScheduledStop;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Groups;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

/**
 * Departure pairing, done on every departures request, with each collection flavour.
 *
 * `production` is the size of a usual request, `stress` a busy stop with a long timetable. phpbench reports the peak
 * memory of the whole process, so the set-up resets it: the peak is the fixture kept in memory plus the subject.
 */
#[Groups(['request']), ParamProviders('provideSizes'), BeforeMethods('setUp'), Revs(200), Iterations(10), Warmup(2)]
final class DeparturePairingBench
{
    private DeparturePairingVariants $variants;

    /**
     * @var list<ScheduledStop>
     */
    private array $schedule;

    /**
     * @var list<Departure>
     */
    private array $real;

    public function provideSizes(): iterable
    {
        yield 'production' => [
            'scheduled' => 32,
            'realtime'  => 16,
        ];
        yield 'stress' => [
            'scheduled' => 512,
            'realtime'  => 256,
        ];
    }

    /**
     * @param array{scheduled: int, realtime: int} $params
     */
    public function setUp(array $params): void
    {
        $this->variants                = new DeparturePairingVariants();
        [$this->schedule, $this->real] = DeparturePairingVariants::fixture($params['scheduled'], $params['realtime']);

        memory_reset_peak_usage();
    }

    public function benchMaterialized(): void
    {
        $this->variants->materialized($this->schedule, $this->real);
    }

    public function benchLazy(): void
    {
        $this->variants->lazy($this->schedule, $this->real);
    }
}
