<?php

namespace App\Model;

use Carbon\Carbon;

class ScheduledStop extends TrackStop
{
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
