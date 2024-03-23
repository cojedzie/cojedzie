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

namespace App\Provider\ZtmGdansk;

use App\Dto\Departure;
use App\Dto\Line;
use App\Dto\ScheduledStop;
use App\Dto\Stop;
use App\Dto\Vehicle;
use App\Filter\Requirement\Embed;
use App\Filter\Requirement\FieldFilter;
use App\Filter\Requirement\FieldFilterOperator;
use App\Filter\Requirement\IdConstraint;
use App\Filter\Requirement\LimitConstraint;
use App\Filter\Requirement\RelatedFilter;
use App\Filter\Requirement\Requirement;
use App\Provider\DepartureRepository;
use App\Provider\LineRepository;
use App\Provider\ScheduleRepository;
use App\Service\Proxy\ReferenceFactory;
use App\Utility\IterableUtils;
use App\Utility\ModifierUtils;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Kadet\Functional\Transforms as t;
use function App\Functions\setup;
use function Kadet\Functional\ref;

class ZtmGdanskDepartureRepository implements DepartureRepository
{
    final public const ESTIMATES_URL = 'http://ckan2.multimediagdansk.pl/delays';

    public function __construct(
        private readonly LineRepository $lines,
        private readonly ScheduleRepository $schedule,
        private readonly ReferenceFactory $reference
    ) {
    }

    public function current(iterable $stops, Requirement ...$requirements)
    {
        $real = IterableUtils::toCollection($stops)
            ->flatMap(ref($this->getRealDepartures(...)))
            ->sortBy(t\property('estimated'))
        ;

        $now       = Carbon::now()->second(0);
        $first     = $real->map(t\getter('scheduled'))->min() ?? $now;
        $scheduled = $this->getScheduledDepartures($stops, $first, ...$this->extractRequirements($requirements));

        $result = $this->pair($scheduled, $real)->filter(fn (Departure $departure) => $departure->getDeparture() > $now);

        return $this->processResultWithRequirements($result, $requirements)->values();
    }

    private function getRealDepartures(Stop $stop)
    {
        try {
            $estimates = file_get_contents(static::ESTIMATES_URL . "?stopId=" . $stop->getId());
            $estimates = json_decode($estimates, true, 512, JSON_THROW_ON_ERROR)['delay'];
        } catch (\Error) {
            return collect();
        }

        $estimates = collect($estimates);

        $lines = $estimates->map(fn ($delay) => $delay['routeId'])->unique();

        $lines = $this->lines->all(new IdConstraint($lines))->keyBy(t\property('id'));

        return collect($estimates)->map(function ($delay) use ($stop, $lines) {
            $scheduled = (new Carbon($delay['theoreticalTime'], 'Europe/Warsaw'))->tz('UTC');
            $estimated = (clone $scheduled)->addSeconds($delay['delayInSeconds']);

            return Departure::createFromArray([
                'key'       => sprintf('%s::%s', $delay['routeId'], $scheduled->format('H:i')),
                'scheduled' => $scheduled,
                'estimated' => $estimated,
                'stop'      => $stop,
                'display'   => trim((string) $delay['headsign']),
                'vehicle'   => $this->reference->get(Vehicle::class, $delay['vehicleCode']),
                'line'      => $lines->get($delay['routeId']) ?: Line::createFromArray([
                    'symbol' => $delay['routeId'],
                    'type'   => Line::TYPE_UNKNOWN,
                ]),
            ]);
        })->values();
    }

    private function getScheduledDepartures($stop, Carbon $time, Requirement ...$requirements)
    {
        return $this->schedule->all(
            new RelatedFilter($stop, Stop::class),
            new FieldFilter('departure', $time, FieldFilterOperator::GreaterOrEqual),
            new Embed('track'),
            new Embed('destination'),
            ...$requirements
        );
    }

    private function pair(Collection $schedule, Collection $real)
    {
        $key = function ($departure) {
            if ($departure instanceof Departure) {
                return sprintf(
                    "%s::%s",
                    $departure->getLine()->getId(),
                    $departure->getScheduled()->format("H:i")
                );
            } elseif ($departure instanceof ScheduledStop) {
                return sprintf(
                    "%s::%s",
                    $departure->getTrack()->getLine()->getId(),
                    $departure->getDeparture()->format("H:i")
                );
            } else {
                throw new \Exception();
            }
        };

        $schedule = $schedule->keyBy($key)->all();
        $real     = $real->keyBy($key);

        return $real->map(function (Departure $real, $key) use (&$schedule) {
            $scheduled = null;

            if (array_key_exists($key, $schedule)) {
                $scheduled = $schedule[$key];
                unset($schedule[$key]);
            }

            return [
                'estimated' => $real,
                'scheduled' => $scheduled,
            ];
        })->merge(
            collect($schedule)->map(fn (ScheduledStop $scheduled) => [
                'estimated' => null,
                'scheduled' => $scheduled,
            ])
        )->map(
            fn ($pair) => $this->merge($pair['estimated'], $pair['scheduled'])
        )->sortBy(function (Departure $departure) {
            $time = $departure->getEstimated() ?? $departure->getScheduled();
            return $time->getTimestamp();
        });
    }

    private function merge(?Departure $real, ?ScheduledStop $scheduled)
    {
        if (!$real) {
            return $this->convertScheduledStopToDeparture($scheduled);
        }

        if (!$scheduled) {
            return $real;
        }

        return setup(clone $real, function (Departure $departure) use ($scheduled, $real) {
            $departure->setDisplay($this->extractDisplayFromScheduledStop($scheduled));
            $departure->setTrack($scheduled->getTrack());
            $departure->setTrip($scheduled->getTrip());
        });
    }

    private function convertScheduledStopToDeparture(ScheduledStop $stop): Departure
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

    private function extractDisplayFromScheduledStop(ScheduledStop $stop)
    {
        return $stop->getTrack()->getDestination()->getName();
    }

    private function extractRequirements(iterable $requirements)
    {
        $result = [];

        /** @var LimitConstraint $limit */
        if ($limit = ModifierUtils::getOfType($requirements, LimitConstraint::class)) {
            $result[] = new LimitConstraint($limit->getOffset(), $limit->getCount() * 2);
        } else {
            $result[] = LimitConstraint::count(16);
        }

        return $result;
    }

    private function processResultWithRequirements(Collection $result, iterable $requirements)
    {
        foreach ($requirements as $modifier) {
            switch (true) {
                case $modifier instanceof LimitConstraint:
                    $result = $result->slice($modifier->getOffset(), $modifier->getCount());
                    break;
            }
        }

        return $result;
    }
}
