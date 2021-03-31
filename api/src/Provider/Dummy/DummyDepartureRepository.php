<?php

namespace App\Provider\Dummy;

use App\Model\Departure;
use App\Model\Line;
use App\Model\Vehicle;
use App\Modifier\Modifier;
use App\Provider\DepartureRepository;
use App\Service\Proxy\ReferenceFactory;
use Carbon\Carbon;

class DummyDepartureRepository implements DepartureRepository
{
    private $reference;

    /**
     * DummyDepartureProviderRepository constructor.
     *
     * @param $reference
     */
    public function __construct(ReferenceFactory $reference)
    {
        $this->reference = $reference;
    }

    public function current(iterable $stops, Modifier ...$modifiers)
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
        ])->map(function ($departure) use ($stop) {
            [$symbol, $type, $display, $vehicle] = $departure;
            $scheduled = new Carbon();
            $estimated = (clone $scheduled)->addSeconds(40);

            return Departure::createFromArray([
                'scheduled' => $scheduled,
                'estimated' => $estimated,
                'stop'      => $stop,
                'display'   => $display,
                'vehicle'   => $this->reference->get(Vehicle::class, $vehicle),
                'line'      => Line::createFromArray(['symbol' => $symbol, 'type' => $type]),
            ]);
        });
    }
}
