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

namespace App\Provider\Dummy;

use App\Dto\Departure;
use App\Dto\Line;
use App\Dto\Vehicle;
use App\Filter\Requirement\Requirement;
use App\Provider\DepartureRepository;
use App\Service\Proxy\ReferenceFactory;
use Carbon\Carbon;

class DummyDepartureRepository implements DepartureRepository
{
    public function __construct(
        private readonly ReferenceFactory $reference
    ) {
    }

    #[\Override]
    public function current(iterable $stops, Requirement ...$requirements)
    {
        return collect([
            [1, Line::TYPE_TRAM, 'lorem ipsum', 2137],
            [1, Line::TYPE_TRAM, 'lorem ipsum', 2137],
            [1, Line::TYPE_TRAM, 'lorem ipsum', 2137],
            [1, Line::TYPE_TRAM, 'lorem ipsum', 2137],
            [1, Line::TYPE_TRAM, 'lorem ipsum', 2137],
            [1, Line::TYPE_TRAM, 'lorem ipsum', 2137],
            [1, Line::TYPE_TRAM, 'lorem ipsum', 2137],
            [1, Line::TYPE_TRAM, 'lorem ipsum', 2137],
            [1, Line::TYPE_TRAM, 'lorem ipsum', 2137],
            [1, Line::TYPE_TRAM, 'lorem ipsum', 2137],
        ])->map(function ($departure) {
            [$symbol, $type, $display, $vehicle] = $departure;
            $scheduled                           = new Carbon();
            $estimated                           = (clone $scheduled)->addSeconds(40);

            return Departure::createFromArray([
                'scheduled' => $scheduled,
                'estimated' => $estimated,
                'display'   => $display,
                'vehicle'   => $this->reference->get(Vehicle::class, $vehicle),
                'line'      => Line::createFromArray(['symbol' => $symbol,
                    'type'                                     => $type,
                ]),
            ]);
        })->values();
    }
}
