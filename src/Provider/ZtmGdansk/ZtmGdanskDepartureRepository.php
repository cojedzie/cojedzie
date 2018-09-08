<?php

namespace App\Provider\ZtmGdansk;

use App\Model\Departure;
use App\Model\Stop;
use App\Provider\DepartureRepository;
use App\Provider\LineRepository;
use Carbon\Carbon;
use Tightenco\Collect\Support\Collection;
use Kadet\Functional\Transforms as t;

class ZtmGdanskDepartureRepository implements DepartureRepository
{
    const ESTIMATES_URL = 'http://87.98.237.99:88/delays';

    /** @var LineRepository */
    private $lines;

    /**
     * @param LineRepository $lines
     */
    public function __construct(LineRepository $lines)
    {
        $this->lines = $lines;
    }

    public function getForStop(Stop $stop): Collection
    {
        $estimates = json_decode(file_get_contents(static::ESTIMATES_URL . "?stopId=" . $stop->getId()), true)['delay'];
        $estimates = collect($estimates);

        $lines = $estimates->map(function ($delay) { return $delay['routeId']; })->unique();
        $lines = $this->lines->getManyById($lines)->keyBy(t\property('id'));

        return collect($estimates)->map(function ($delay) use ($stop, $lines) {
            $scheduled = new Carbon($delay['theoreticalTime']);
            $estimated = (clone $scheduled)->addSeconds($delay['delayInSeconds']);

            return Departure::createFromArray([
                'scheduled' => $scheduled,
                'estimated' => $estimated,
                'stop'      => $stop,
                'display'   => trim($delay['headsign']),
                'vehicle'   => $delay['vehicleCode'],
                'line'      => $lines->get($delay['routeId']),
            ]);
        })->values();
    }
}