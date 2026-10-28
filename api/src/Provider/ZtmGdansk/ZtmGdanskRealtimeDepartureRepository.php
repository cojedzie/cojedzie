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
use Carbon\Carbon;
use Ds\Seq;
use Kadet\Functional\Collections as c;
use Kadet\Functional\Transforms as t;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Live departures of the given stops from ZTM Gdańsk's TRISTAR departures API: estimates for vehicles on the way,
 * timetable-only entries without an estimate.
 */
#[Exclude] // built by ZtmGdanskProvider with its own line repository and the configured URL
class ZtmGdanskRealtimeDepartureRepository implements DepartureRepository
{
    final public const string DEPARTURES_PATH = '/departures';

    public function __construct(
        private readonly LineRepository $lines,
        private readonly ReferenceFactory $reference,
        private readonly ZtmGdanskConfig $config,
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
        $response = @file_get_contents($this->config->apiUrl . self::DEPARTURES_PATH . "?stopId=" . $stop->getId());
        if ($response === false) {
            return new Seq();
        }

        try {
            $departures = new Seq(json_decode($response, true, 512, JSON_THROW_ON_ERROR)['departures'] ?? []);
        } catch (\JsonException) {
            return new Seq();
        }

        $lines = $this->lines
            ->all(new IdConstraint($departures |> c\map(fn (array $departure) => (string) $departure['routeId']) |> c\unique()))
            |> c\keyBy(t\property('id'));

        return $departures |> c\map(function (array $departure) use ($stop, $lines) {
            $scheduled = new Carbon($departure['theoreticalTime'])->tz('UTC');
            // entries the API knows only from its own timetable carry no estimate
            $realtime = ($departure['status'] ?? null) === 'REALTIME' && $departure['delayInSeconds'] !== null;

            return Departure::createFromArray([
                'key'       => sprintf('%s::%s', $departure['routeId'], $scheduled->format('H:i')),
                'scheduled' => $scheduled,
                'estimated' => $realtime ? new Carbon($departure['estimatedTime'])->tz('UTC') : null,
                'stop'      => $stop,
                'display'   => trim((string) $departure['headsign']),
                'vehicle'   => $departure['vehicleCode'] !== null
                    ? $this->reference->get(Vehicle::class, $departure['vehicleCode'])
                    : null,
                'line'      => $lines->get((string) $departure['routeId'], null) ?: Line::createFromArray([
                    'id'     => (string) $departure['routeId'],
                    'symbol' => (string) ($departure['routeShortName'] ?? $departure['routeId']),
                    'type'   => Line::TYPE_UNKNOWN,
                ]),
            ]);
        });
    }
}
