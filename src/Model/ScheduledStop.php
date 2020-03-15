<?php

namespace App\Model;

use Carbon\Carbon;

class ScheduledStop extends TrackStop
{
    /**
     * Arrival time.
     * @var Carbon
     */
    private $arrival;

    /**
     * Departure time.
     * @var Carbon
     */
    private $departure;

    /**
     * Exact trip that this scheduled stop is part of.
     * @var Trip|null
     */
    private $trip;

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

    public function getTrip(): ?Trip
    {
        return $this->trip;
    }

    public function setTrip(?Trip $trip): void
    {
        $this->trip = $trip;
    }
}
