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
use App\Provider\Live\LiveDeparture;
use App\Provider\Live\LiveDepartureException;
use Carbon\Carbon;
use Ds\Seq;
use Kadet\Functional\Collections as c;
use Kadet\Functional\Transforms as t;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Live departures of the given stops from ZTM Gdańsk's TRISTAR departures API: estimates for vehicles on the way,
 * timetable-only entries without an estimate.
 */
#[Exclude] // built by ZtmGdanskProvider with its own line repository
class ZtmGdanskRealtimeDepartureRepository implements DepartureRepository
{
    public function __construct(
        private readonly LineRepository $lines,
        private readonly ZtmGdanskLiveDepartures $live,
    ) {
    }

    #[\Override]
    public function current(iterable $stops, Requirement ...$requirements): Seq
    {
        return $stops |> c\toSeq() |> c\flatMap($this->getRealDepartures(...));
    }

    /**
     * @return Seq<Departure>
     */
    private function getRealDepartures(Stop $stop): Seq
    {
        // without the API (unreachable or answering garbage) the timetable is shown on its own
        try {
            $departures = $this->live->forStop($stop->getId());
        } catch (LiveDepartureException) {
            return new Seq();
        }

        $lines = $this->lines
            ->all(new IdConstraint($departures |> c\map(t\property('lineId')) |> c\unique()))
            |> c\keyBy(t\property('id'));

        return $departures |> c\map(function (LiveDeparture $departure) use ($stop, $lines) {
            $scheduled = Carbon::instance($departure->scheduledAt)->tz('UTC');

            return Departure::createFromArray([
                'key'       => sprintf('%s::%s', $departure->lineId, $scheduled->format('H:i')),
                'scheduled' => $scheduled,
                'estimated' => $departure->estimatedAt !== null ? Carbon::instance($departure->estimatedAt)->tz('UTC') : null,
                'stop'      => $stop,
                'display'   => $departure->headsign,
                'vehicle'   => $departure->vehicle !== null ? Vehicle::reference($departure->vehicle) : null,
                'line'      => $lines->get($departure->lineId, null) ?: Line::createFromArray([
                    'id'     => $departure->lineId,
                    'symbol' => $departure->lineSymbol,
                    'type'   => Line::TYPE_UNKNOWN,
                ]),
            ]);
        });
    }
}
