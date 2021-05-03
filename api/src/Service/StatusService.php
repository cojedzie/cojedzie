<?php

namespace App\Service;

use App\Model\Status\Aggregated;
use App\Model\Status\Endpoint;
use App\Model\Status\Time;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

class StatusService
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function getAggregatedStatus(): Aggregated
    {
        return Aggregated::createFromArray([
            'time'      => $this->getTimeStatus(),
            'endpoints' => $this->getEndpointsStatus(),
        ]);
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
