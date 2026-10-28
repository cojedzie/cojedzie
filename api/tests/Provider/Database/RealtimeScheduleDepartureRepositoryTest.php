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

namespace App\Tests\Provider\Database;

use App\Dto\Departure;
use App\Dto\Stop;
use App\Filter\Requirement\LimitConstraint;
use App\Filter\Requirement\Requirement;
use App\Provider\Database\RealtimeScheduleDepartureRepository;
use App\Provider\DepartureRepository;
use App\Provider\ScheduleRepository;
use App\Tests\Benchmark\Collections\DeparturePairingVariants;
use Carbon\Carbon;
use Ds\Seq;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * Pins the output of the timetable and live departures join for a small fixture.
 */
final class RealtimeScheduleDepartureRepositoryTest extends TestCase
{
    public function testJoinsTimetableWithLiveDepartures(): void
    {
        $departures = $this->repository('2026-01-01 08:20')->current([$this->stop()]);

        $this->assertSame([
            'L4 08:22 - Dest 4',
            'L5 08:24 08:25 Dest 5',
            'L6 08:26 - Dest 6',
            'L7 08:28 08:30 Dest 7',
            'L8 08:30 - Dest 8',
            'L2 08:34 - Dest 2',
            'L1 08:32 08:35 Dest 1',
            'L4 08:38 - Dest 4',
            'L3 08:36 08:40 Dest 3',
            'L5 08:40 08:40 Dest 5',
            'L6 08:42 - Dest 6',
            'L7 08:44 - Dest 7',
            'L7 08:45 08:46 Realtime 7',
            'L8 08:46 - Dest 8',
            'L1 08:48 - Dest 1',
            'L2 08:50 - Dest 2',
            'L1 08:49 08:51 Realtime 1',
            'L3 08:52 - Dest 3',
            'L4 08:54 - Dest 4',
            'L3 08:53 08:56 Realtime 3',
            'L5 08:56 - Dest 5',
            'L6 08:58 - Dest 6',
            'L7 09:00 - Dest 7',
            'L5 08:57 09:01 Realtime 5',
            'L7 09:01 09:01 Realtime 7',
            'L8 09:02 - Dest 8',
        ], $this->describe($departures));
    }

    public function testAppliesTheLimitToTheJoinedDepartures(): void
    {
        $departures = $this->repository('2026-01-01 08:20')->current([$this->stop()], new LimitConstraint(3, 5));

        $this->assertSame([
            'L7 08:28 08:30 Dest 7',
            'L8 08:30 - Dest 8',
            'L2 08:34 - Dest 2',
            'L1 08:32 08:35 Dest 1',
            'L4 08:38 - Dest 4',
        ], $this->describe($departures));
    }

    private function repository(string $now): RealtimeScheduleDepartureRepository
    {
        [$schedule, $real] = DeparturePairingVariants::fixture(32, 16);

        return new RealtimeScheduleDepartureRepository(
            new readonly class($schedule) implements ScheduleRepository {
                public function __construct(
                    private array $schedule
                ) {
                }

                #[\Override]
                public function getDeparturesForStop(Stop $stop, Carbon $from, int $count = self::DEFAULT_DEPARTURES_COUNT): Seq
                {
                    return new Seq();
                }

                #[\Override]
                public function first(Requirement ...$requirements)
                {
                    return null;
                }

                #[\Override]
                public function all(Requirement ...$requirements): Seq
                {
                    return new Seq($this->schedule);
                }
            },
            new readonly class($real) implements DepartureRepository {
                public function __construct(
                    private array $real
                ) {
                }

                #[\Override]
                public function current(iterable $stops, Requirement ...$requirements): Seq
                {
                    return new Seq($this->real);
                }
            },
            new MockClock(Carbon::parse($now)->toDateTimeImmutable()),
        );
    }

    private function stop(): Stop
    {
        return Stop::createFromArray([
            'id'   => 'S1',
            'name' => 'Stop',
        ]);
    }

    /**
     * @return list<string>
     */
    private function describe(Seq $departures): array
    {
        $result = [];
        /** @var Departure $departure */
        foreach ($departures as $departure) {
            $result[] = sprintf(
                '%s %s %s %s',
                $departure->getLine()->getId(),
                $departure->getScheduled()->format('H:i'),
                $departure->getEstimated()?->format('H:i') ?? '-',
                $departure->getDisplay(),
            );
        }

        return $result;
    }
}
