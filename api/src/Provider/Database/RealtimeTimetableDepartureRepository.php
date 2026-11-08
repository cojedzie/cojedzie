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

namespace App\Provider\Database;

use App\Dto\Departure;
use App\Dto\Stop;
use App\Filter\Requirement\FieldFilter;
use App\Filter\Requirement\FieldFilterOperator;
use App\Filter\Requirement\LimitConstraint;
use App\Filter\Requirement\RelatedFilter;
use App\Filter\Requirement\Requirement;
use App\Provider\DeparturePairing;
use App\Provider\DepartureRepository;
use App\Provider\TimetableRepository;
use App\Utility\ModifierUtils;
use Carbon\Carbon;
use Ds\Seq;
use Kadet\Functional\Collections as c;
use Kadet\Functional\Transforms as t;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use function App\Functions\setup;

/**
 * Departures from a timetable joined with live departures from another repository: a live departure replaces the
 * timetable entry of the same line and scheduled time, live departures without one are kept, and departures that
 * already left are dropped. Providers can change how entries are matched or merged by overriding pair() and merge().
 */
#[Exclude] // built by each provider from its own timetable and live source
class RealtimeTimetableDepartureRepository implements DepartureRepository
{
    public function __construct(
        private readonly TimetableRepository $timetable,
        private readonly DepartureRepository $realtime,
        private readonly ClockInterface $clock,
    ) {
    }

    #[\Override]
    public function current(iterable $stops, Requirement ...$requirements): Seq
    {
        $real = $this->realtime->current($stops) |> c\sortBy(t\property('estimated'));

        $now = Carbon::instance($this->clock->now())->second(0);
        // timetable from now, or earlier when a live departure was scheduled before now (it is late)
        $first   = $real |> c\map(t\getter('scheduled')) |> c\append($now) |> c\min();
        $planned = $this->getPlannedDepartures($stops, $first, ...$this->extractRequirements($requirements));

        $result = $this->pair($planned, $real)
            |> c\filter(fn (Departure $departure) => $departure->getDeparture() > $now);

        return $this->processResultWithRequirements($result, $requirements);
    }

    private function getPlannedDepartures($stop, Carbon $time, Requirement ...$requirements): Seq
    {
        return $this->timetable->departures(
            new RelatedFilter($stop, Stop::class),
            new FieldFilter('departure', $time, FieldFilterOperator::GreaterOrEqual),
            ...$requirements
        );
    }

    /**
     * @param Seq<Departure> $planned
     * @param Seq<Departure> $real
     *
     * @return Seq<Departure>
     */
    protected function pair(Seq $planned, Seq $real): Seq
    {
        $pairs = DeparturePairing::pair($planned, $real, self::key(...), self::key(...));

        // departures that tie on time keep the order the sort receives them in: live ones go before planned-only ones
        return $pairs
            |> c\filter(fn (array $pair) => $pair['live'] !== null)
            |> c\concat($pairs |> c\filter(fn (array $pair) => $pair['live'] === null))
            |> c\map(fn (array $pair) => $this->merge($pair['live'], $pair['planned']))
            |> c\sortBy(function (Departure $departure) {
                $time = $departure->getEstimated() ?? $departure->getScheduled();
                return $time->getTimestamp();
            });
    }

    private static function key(Departure $departure): string
    {
        return DeparturePairing::key(
            $departure->getStop()->getId(),
            $departure->getLine()->getId(),
            $departure->getScheduled()
        );
    }

    protected function merge(?Departure $live, ?Departure $planned): Departure
    {
        if (!$live) {
            return $planned;
        }

        if (!$planned) {
            return $live;
        }

        return setup(clone $live, function (Departure $departure) use ($planned) {
            $departure->setDisplay($planned->getDisplay());
            $departure->setTrack($planned->getTrack());
            $departure->setTrip($planned->getTrip());
            $departure->setServiceDay($planned->getServiceDay());
        });
    }

    private function extractRequirements(iterable $requirements)
    {
        $result = [];

        $limit = ModifierUtils::getOfType($requirements, LimitConstraint::class);

        if ($limit instanceof LimitConstraint) {
            // the offset is applied once, to the joined departures
            $result[] = new LimitConstraint(0, ($limit->getOffset() + $limit->getCount()) * 2);
        } else {
            $result[] = LimitConstraint::count(16);
        }

        return $result;
    }

    private function processResultWithRequirements(Seq $result, iterable $requirements): Seq
    {
        foreach ($requirements as $modifier) {
            switch (true) {
                case $modifier instanceof LimitConstraint:
                    $result = $result |> c\slice($modifier->getOffset(), $modifier->getCount());
                    break;
            }
        }

        return $result;
    }
}
