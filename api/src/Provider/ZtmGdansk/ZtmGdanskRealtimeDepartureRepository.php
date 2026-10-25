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
use App\Dto\Stop;
use App\Dto\Vehicle;
use App\Filter\Requirement\IdConstraint;
use App\Filter\Requirement\Requirement;
use App\Provider\DepartureRepository;
use App\Provider\LineRepository;
use App\Service\Proxy\ReferenceFactory;
use App\Utility\IterableUtils;
use Carbon\Carbon;
use Kadet\Functional\Transforms as t;
use function Kadet\Functional\ref;

/**
 * Live departures (with estimates) of the given stops from ZTM Gdańsk's delays API.
 */
class ZtmGdanskRealtimeDepartureRepository implements DepartureRepository
{
    final public const string ESTIMATES_URL = 'http://ckan2.multimediagdansk.pl/delays';

    public function __construct(
        private readonly LineRepository $lines,
        private readonly ReferenceFactory $reference
    ) {
    }

    #[\Override]
    public function current(iterable $stops, Requirement ...$requirements)
    {
        return IterableUtils::toCollection($stops)
            ->flatMap(ref($this->getRealDepartures(...)))
            ->values();
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
            $scheduled = new Carbon($delay['theoreticalTime'], 'Europe/Warsaw')->tz('UTC');
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
}
