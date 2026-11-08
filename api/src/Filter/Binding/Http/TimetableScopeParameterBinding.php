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

namespace App\Filter\Binding\Http;

use App\Dto\Line;
use App\Dto\Stop;
use App\Dto\Track;
use App\Dto\Trip;
use App\Filter\Binding\Http\Exception\InvalidParameterException;
use Attribute;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Stops, lines, tracks and trips a timetable is made of, from the route or the query. At least one of them is
 * required.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PARAMETER)]
class TimetableScopeParameterBinding implements ParameterBinding
{
    /**
     * @var list<RelatedFilterParameterBinding>
     */
    private readonly array $bindings;

    public function __construct()
    {
        $resources = [
            'stop'  => Stop::class,
            'line'  => Line::class,
            'track' => Track::class,
            'trip'  => Trip::class,
        ];

        $this->bindings = array_map(
            fn (string $parameter, string $resource) => new RelatedFilterParameterBinding(
                resource: $resource,
                parameter: $parameter,
                from: ['attributes', 'query'],
                documentation: [
                    'description' => sprintf(
                        '%ss to include. At least one of stop, line, track and trip is required.',
                        ucfirst($parameter),
                    ),
                ],
            ),
            array_keys($resources),
            $resources,
        );
    }

    #[\Override]
    public function getRequirementsFromRequest(Request $request): iterable
    {
        $requirements = [];

        foreach ($this->bindings as $binding) {
            array_push($requirements, ...$binding->getRequirementsFromRequest($request));
        }

        if ($requirements === []) {
            throw InvalidParameterException::noneOf(['stop', 'line', 'track', 'trip']);
        }

        yield from $requirements;
    }

    #[\Override]
    public function getDocumentation(Route $route): iterable
    {
        foreach ($this->bindings as $binding) {
            yield from $binding->getDocumentation($route);
        }
    }
}
