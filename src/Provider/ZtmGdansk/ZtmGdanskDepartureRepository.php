<?php


namespace App\Provider\ZtmGdansk;


use App\Model\Departure;
use App\Model\Line;
use App\Model\Stop;
use App\Provider\DepartureRepository;
use Carbon\Carbon;
use Tightenco\Collect\Support\Collection;

class ZtmGdanskDepartureRepository implements DepartureRepository
{
    const ESTIMATES_URL = 'http://87.98.237.99:88/delays';

    private $lines;

    /**
     * ZtmGdanskDepartureRepository constructor.
     *
     * @param $lines
     */
    public function __construct(ZtmGdanskLineRepository $lines)
    {
        $this->lines = $lines;
    }

    public function getForStop(Stop $stop): Collection
    {
        try {
            $estimates  = json_decode(file_get_contents(static::ESTIMATES_URL . "?stopId=" . $stop->getId()), true)['delay'];

            return collect($estimates)->map(function ($delay) use ($stop) {
                $scheduled = new Carbon($delay['theoreticalTime']);
                $estimated = (clone $scheduled)->addSeconds($delay['delayInSeconds']);

                return Departure::createFromArray([
                    'scheduled' => $scheduled,
                    'estimated' => $estimated,
                    'stop'      => $stop,
                    'display'   => trim($delay['headsign']),
                    'vehicle'   => $delay['vehicleCode'],
                    'line'      => Line::createFromArray([
                        'id'     => $delay['id'],
                        'symbol' => $delay['routeId'],
                        'type'   => $delay['routeId'] > 1 && $delay['routeId'] <= 12 ? Line::TYPE_TRAM : Line::TYPE_BUS
                    ])
                ]);
            })->values();
        } catch (\Throwable $error) {
            return collect();
        }
    }
}