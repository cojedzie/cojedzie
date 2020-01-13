<?php

namespace App\Provider\ZtmGdansk;

use App\Model\Departure;
use App\Model\Line;
use App\Model\Stop;
use App\Model\Vehicle;
use App\Provider\Database\GenericScheduleRepository;
use App\Provider\DepartureRepository;
use App\Provider\LineRepository;
use App\Provider\ScheduleRepository;
use App\Service\Proxy\ReferenceFactory;
use Carbon\Carbon;
use JMS\Serializer\Tests\Fixtures\Discriminator\Car;
use Tightenco\Collect\Support\Collection;
use Kadet\Functional\Transforms as t;

class ZtmGdanskDepartureRepository implements DepartureRepository
{
    const ESTIMATES_URL = 'http://ckan2.multimediagdansk.pl/delays';

    /** @var LineRepository */
    private $lines;

    /** @var ReferenceFactory */
    private $reference;

    /** @var ScheduleRepository */
    private $schedule;

    /**
     * @param LineRepository $lines
     */
    public function __construct(LineRepository $lines, ScheduleRepository $schedule, ReferenceFactory $reference)
    {
        $this->lines = $lines;
        $this->reference = $reference;
        $this->schedule = $schedule;
    }

    public function getForStop(Stop $stop): Collection
    {
        $real      = $this->getRealDepartures($stop);
        $now       = Carbon::now('UTC');
        $first     = $real->map(t\getter('scheduled'))->min() ?? $now;
        $scheduled = $this->getScheduledDepartures($stop, $first < $now ? $now : $first);

        return $this->pair($scheduled, $real);
    }

    private function getRealDepartures(Stop $stop)
    {
        $estimates = json_decode(file_get_contents(static::ESTIMATES_URL . "?stopId=" . $stop->getId()), true)['delay'];
        $estimates = collect($estimates);

        $lines = $estimates->map(function ($delay) {
            return $delay['routeId'];
        })->unique();
        $lines = $this->lines->getManyById($lines)->keyBy(t\property('id'));

        return collect($estimates)->map(function ($delay) use ($stop, $lines) {
            $scheduled = (new Carbon($delay['theoreticalTime'], 'Europe/Warsaw'))->tz('UTC');
            $estimated = (clone $scheduled)->addSeconds($delay['delayInSeconds']);

            return Departure::createFromArray([
                'scheduled' => $scheduled,
                'estimated' => $estimated,
                'stop'      => $stop,
                'display'   => trim($delay['headsign']),
                'vehicle'   => $this->reference->get(Vehicle::class, $delay['vehicleCode']),
                'line'      => $lines->get($delay['routeId']) ?: Line::createFromArray([
                    'symbol' => $delay['routeId'],
                    'type'   => Line::TYPE_UNKNOWN,
                ]),
            ]);
        })->values();
    }

    private function getScheduledDepartures(Stop $stop, Carbon $time)
    {
        return $this->schedule->getDeparturesForStop($stop, $time);
    }

    private function pair(Collection $schedule, Collection $real)
    {
        $key = function (Departure $departure) {
            return sprintf("%s::%s", $departure->getLine()->getSymbol(), $departure->getScheduled()->format("H:i"));
        };

        $schedule = $schedule->keyBy($key)->all();
        $real     = $real->keyBy($key);

        return $real->map(function (Departure $real, $key) use (&$schedule) {
            $scheduled = null;

            if (array_key_exists($key, $schedule)) {
                $scheduled = $schedule[$key];
                unset($schedule[$key]);
            }

            return [ $real, $scheduled ];
        })->merge(collect($schedule)->map(function (Departure $scheduled) {
            return [ null, $scheduled ];
        }))->map(function ($pair) {
            return $pair[0] ?? $pair[1];
        })->sortBy(function (Departure $departure) {
            $time = $departure->getEstimated() ?? $departure->getScheduled();
            return $time->getTimestamp();
        });
    }
}
