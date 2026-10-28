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
use App\Dto\ScheduledStop;
use App\Dto\Stop;
use App\Filter\Requirement\Embed;
use App\Filter\Requirement\FieldFilter;
use App\Filter\Requirement\FieldFilterOperator;
use App\Filter\Requirement\LimitConstraint;
use App\Filter\Requirement\RelatedFilter;
use App\Filter\Requirement\Requirement;
use App\Provider\DepartureRepository;
use App\Provider\ScheduleRepository;
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
#[Exclude] // built by each provider from its own schedule and live source
class RealtimeScheduleDepartureRepository implements DepartureRepository
{
    public function __construct(
        private readonly ScheduleRepository $schedule,
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
        $first     = $real |> c\map(t\getter('scheduled')) |> c\append($now) |> c\min();
        $scheduled = $this->getScheduledDepartures($stops, $first, ...$this->extractRequirements($requirements));

        $result = $this->pair($scheduled, $real)
            |> c\filter(fn (Departure $departure) => $departure->getDeparture() > $now);

        return $this->processResultWithRequirements($result, $requirements);
    }

    private function getScheduledDepartures($stop, Carbon $time, Requirement ...$requirements): Seq
    {
        return $this->schedule->all(
            new RelatedFilter($stop, Stop::class),
            new FieldFilter('departure', $time, FieldFilterOperator::GreaterOrEqual),
            new Embed('track'),
            new Embed('destination'),
            ...$requirements
        );
    }

    /**
     * @param Seq<ScheduledStop> $schedule
     * @param Seq<Departure>     $real
     *
     * @return Seq<Departure>
     */
    protected function pair(Seq $schedule, Seq $real): Seq
    {
        $key = function ($departure) {
            if ($departure instanceof Departure) {
                return sprintf(
                    "%s::%s::%s",
                    $departure->getStop()->getId(),
                    $departure->getLine()->getId(),
                    $departure->getScheduled()->format("H:i")
                );
            } elseif ($departure instanceof ScheduledStop) {
                return sprintf(
                    "%s::%s::%s",
                    $departure->getStop()->getId(),
                    $departure->getTrack()->getLine()->getId(),
                    $departure->getDeparture()->format("H:i")
                );
            } else {
                throw new \Exception();
            }
        };

        // a new map, so taking the matched entries out of it leaves the given schedule untouched
        $schedule = $schedule |> c\keyBy($key);

        $pairs = $real
            |> c\keyBy($key)
            |> c\map(fn (Departure $real, string $key) => [
                'estimated' => $real,
                'scheduled' => $schedule->remove($key, null),
            ]);

        /** @var Seq<array{estimated: Departure|null, scheduled: ScheduledStop|null}> $unmatched */
        $unmatched = $schedule
            |> c\map(fn (ScheduledStop $scheduled) => [
                'estimated' => null,
                'scheduled' => $scheduled,
            ]);

        return $pairs
            |> c\concat($unmatched)
            |> c\map(fn (array $pair) => $this->merge($pair['estimated'], $pair['scheduled']))
            |> c\sortBy(function (Departure $departure) {
                $time = $departure->getEstimated() ?? $departure->getScheduled();
                return $time->getTimestamp();
            });
    }

    protected function merge(?Departure $real, ?ScheduledStop $scheduled): Departure
    {
        if (!$real) {
            return $this->convertScheduledStopToDeparture($scheduled);
        }

        if (!$scheduled) {
            return $real;
        }

        return setup(clone $real, function (Departure $departure) use ($scheduled) {
            $departure->setDisplay($this->extractDisplayFromScheduledStop($scheduled));
            $departure->setTrack($scheduled->getTrack());
            $departure->setTrip($scheduled->getTrip());
        });
    }

    protected function convertScheduledStopToDeparture(ScheduledStop $stop): Departure
    {
        return setup(new Departure(), function (Departure $converted) use ($stop) {
            $converted->setDisplay($this->extractDisplayFromScheduledStop($stop));
            $converted->setLine($stop->getTrack()->getLine());
            $converted->setTrack($stop->getTrack());
            $converted->setTrip($stop->getTrip());
            $converted->setScheduled($stop->getDeparture());
            $converted->setStop($stop->getStop());
        });
    }

    protected function extractDisplayFromScheduledStop(ScheduledStop $stop)
    {
        return $stop->getTrack()->getDestination()->getName();
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
