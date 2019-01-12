<?php

namespace App\Model;

use Carbon\Carbon;

class ScheduleStop implements Fillable
{
    use FillTrait;

    /**
     * Stop (as a place) related to that scheduled bus stop
     * @var Stop
     */
    private $stop;

    /**
     * Order in trip
     * @var int
     */
    private $order;

    /**
     * Arrival time
     * @var Carbon
     */
    private $arrival;

    /**
     * Departure time
     * @var Carbon
     */
    private $departure;

    public function getStop()
    {
        return $this->stop;
    }

    public function setStop($stop): void
    {
        $this->stop = $stop;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): void
    {
        $this->order = $order;
    }

    public function getArrival(): Carbon
    {
        return $this->arrival;
    }

    public function setArrival(Carbon $arrival): void
    {
        $this->arrival = $arrival;
    }

    public function getDeparture(): Carbon
    {
        return $this->departure;
    }

    public function setDeparture(Carbon $departure): void
    {
        $this->departure = $departure;
    }
}