<?php

namespace App\Model;

class TrackStop implements Fillable, DTO
{
    use FillTrait;

    /**
     * Order in trip
     * @var int
     */
    private $order;

    /**
     * Stop (as a place) related to that scheduled bus stop
     * @var Stop
     */
    private $stop;

    /**
     * Track that this stop is part of.
     * @var Track|null
     */
    private $track;

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

    public function getTrack(): ?Track
    {
        return $this->track;
    }

    public function setTrack(?Track $track): void
    {
        $this->track = $track;
    }
}
