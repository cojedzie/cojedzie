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

namespace App\Service;

use App\Dto\Status\Aggregated;
use App\Dto\Status\Endpoint;
use App\Dto\Status\Time;
use App\Dto\Status\Version;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

class StatusService
{
    public function __construct(
        private readonly RouterInterface $router
    ) {
    }

    public function getVersionStatus(): Version
    {
        return new Version(
            version: $_ENV['COJEDZIE_VERSION'],
            revision: $_ENV['COJEDZIE_REVISION'],
        );
    }

    public function getAggregatedStatus(): Aggregated
    {
        return new Aggregated(
            time: $this->getTimeStatus(),
            endpoints: $this->getEndpointsStatus(),
            version: $this->getVersionStatus(),
        );
    }

    public function getEndpointsStatus(): Collection
    {
        $routes = collect($this->router->getRouteCollection()->all());

        return $routes
            ->filter(fn (Route $route) => $route->getOption('version'))
            ->filter(fn (Route $route) => $route->getMethods())
            ->map(fn (Route $route, $name) => Endpoint::createFromArray([
                'name'     => $name,
                'version'  => $route->getOption('version') ?: '1.0',
                'methods'  => $route->getMethods(),
                'template' => $route->getPath(),
            ]))
            ->values()
        ;
    }

    public function getTimeStatus()
    {
        return Time::createFromDateTime(Carbon::now());
    }
}
