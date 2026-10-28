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
use App\Dto\Line;
use App\Dto\ScheduledStop;
use App\Dto\Stop;
use App\Dto\Track;
use App\Provider\Database\RealtimeScheduleDepartureRepository;
use Carbon\Carbon;
use Ds\Seq;
use Kadet\Functional\Collections as c;
use Kadet\Functional\Collections\Lazy as lazy;

/**
 * Variants of departure pairing (RealtimeScheduleDepartureRepository::pair()), the collection work done on every
 * departures request, written with each collection flavour.
 *
 * Every variant returns the merged departures as a list sorted by departure time and does exactly the same work, so
 * they can be compared. All of them merge through the repository's own merge().
 */
final readonly class DeparturePairingVariants
{
    private const int LINES    = 8;
    private const string START = '2026-01-01 08:00';
    private RealtimeScheduleDepartureRepository $repository;

    public function __construct()
    {
        // pair() and merge() don't use the schedule, realtime and clock dependencies, so the constructor skips them
        $this->repository = new class() extends RealtimeScheduleDepartureRepository {
            public function __construct()
            {
            }

            /**
             * @param Seq<ScheduledStop> $schedule
             * @param Seq<Departure>     $real
             *
             * @return Seq<Departure>
             */
            public function pairPublic(Seq $schedule, Seq $real): Seq
            {
                return $this->pair($schedule, $real);
            }

            /**
             * @param array{estimated: ?Departure, scheduled: ?ScheduledStop} $pair
             */
            public function mergePair(array $pair): Departure
            {
                return $this->merge($pair['estimated'], $pair['scheduled']);
            }
        };
    }

    /**
     * The production pair(), to pin the copies in this class to it.
     *
     * @param iterable<ScheduledStop> $schedule
     * @param iterable<Departure>     $real
     *
     * @return list<Departure>
     */
    public function production(iterable $schedule, iterable $real): array
    {
        return $this->repository->pairPublic(new Seq($schedule), new Seq($real))->toArray();
    }

    /**
     * Eager kadet/functional collections: every step builds a finished collection.
     *
     * @param iterable<ScheduledStop> $schedule
     * @param iterable<Departure>     $real
     *
     * @return list<Departure>
     */
    public function materialized(iterable $schedule, iterable $real): array
    {
        $schedule = $schedule |> c\keyBy(self::key(...));

        $pairs = $real
            |> c\keyBy(self::key(...))
            |> c\map(fn (Departure $real, string $key) => [
                'estimated' => $real,
                'scheduled' => $schedule->remove($key, null),
            ]);

        $unmatched = $schedule
            |> c\map(fn (ScheduledStop $scheduled) => [
                'estimated' => null,
                'scheduled' => $scheduled,
            ]);

        return $pairs
            |> c\concat($unmatched)
            |> c\map($this->repository->mergePair(...))
            |> c\sortBy(fn (Departure $departure) => ($departure->getEstimated() ?? $departure->getScheduled())->getTimestamp())
            |> c\toArray();
    }

    /**
     * Lazy kadet/functional collections wherever the step allows it: keying and sorting need the whole input, so
     * they stay eager.
     *
     * @param iterable<ScheduledStop> $schedule
     * @param iterable<Departure>     $real
     *
     * @return list<Departure>
     */
    public function lazy(iterable $schedule, iterable $real): array
    {
        $schedule = $schedule |> c\keyBy(self::key(...));

        return $real
            |> c\keyBy(self::key(...))
            |> lazy\map(fn (Departure $real, string $key) => [
                'estimated' => $real,
                'scheduled' => $schedule->remove($key, null),
            ])
            // read only after every real departure took its match out of the schedule
            |> lazy\concat($schedule |> lazy\map(fn (ScheduledStop $scheduled) => [
                'estimated' => null,
                'scheduled' => $scheduled,
            ]))
            |> lazy\map($this->repository->mergePair(...))
            |> c\sortBy(fn (Departure $departure) => ($departure->getEstimated() ?? $departure->getScheduled())->getTimestamp())
            |> c\toArray();
    }

    /**
     * Departures at stop S1 of lines L1..L8, every 2 minutes from 08:00, so no two of them share a time.
     *
     * The first `round($realtime * $matchRatio)` realtime departures have the line and the scheduled time of a
     * scheduled stop, the rest are scheduled a minute after one, so they match nothing. Every realtime departure,
     * matched or not, is estimated 0 to 4 minutes late and displays `Realtime {n}`; the schedule displays `Dest {n}`.
     *
     * @return array{0: list<ScheduledStop>, 1: list<Departure>}
     */
    public static function fixture(int $scheduled, int $realtime, float $matchRatio = 0.7): array
    {
        $stop = Stop::createFromArray([
            'id'   => 'S1',
            'name' => 'Stop',
        ]);
        $lines = [];

        for ($n = 1; $n <= self::LINES; $n++) {
            $line = Line::createFromArray([
                'id'     => "L{$n}",
                'symbol' => (string) $n,
                'type'   => Line::TYPE_BUS,
            ]);

            $lines[] = [$line, Track::createFromArray([
                'id'          => "T{$n}",
                'line'        => $line,
                'destination' => Stop::createFromArray([
                    'id'   => "D{$n}",
                    'name' => "Dest {$n}",
                ]),
            ])];
        }

        $at = fn (int $minutes) => Carbon::parse(self::START)->addMinutes($minutes);

        $schedule = [];
        for ($i = 0; $i < $scheduled; $i++) {
            [, $track] = $lines[$i % self::LINES];

            $schedule[] = ScheduledStop::createFromArray([
                'order'     => 1,
                'stop'      => $stop,
                'arrival'   => $at(2 * $i),
                'departure' => $at(2 * $i),
                'track'     => $track,
            ]);
        }

        $matched = (int) round($realtime * $matchRatio);
        $real    = [];
        for ($j = 0; $j < $realtime; $j++) {
            // spread over the schedule, each departure next to a different scheduled stop
            $i       = intdiv($j * $scheduled, $realtime);
            [$line]  = $lines[$i % self::LINES];
            $minutes = 2 * $i + ($j < $matched ? 0 : 1);

            $real[] = Departure::createFromArray([
                'stop'      => $stop,
                'line'      => $line,
                'scheduled' => $at($minutes),
                'estimated' => $at($minutes + $j % 5),
                'display'   => sprintf('Realtime %d', $i % self::LINES + 1),
            ]);
        }

        return [$schedule, $real];
    }

    private static function key(Departure|ScheduledStop $departure): string
    {
        return $departure instanceof Departure
            ? sprintf("%s::%s::%s", $departure->getStop()->getId(), $departure->getLine()->getId(), $departure->getScheduled()->format("H:i"))
            : sprintf("%s::%s::%s", $departure->getStop()->getId(), $departure->getTrack()->getLine()->getId(), $departure->getDeparture()->format("H:i"));
    }
}
