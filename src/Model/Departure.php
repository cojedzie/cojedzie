<?php

namespace App\Model;

use Carbon\Carbon;

class Departure implements Fillable
{
    use FillTrait;

    /**
     * Information about line
     * @var \App\Model\Line
     */
    private $line;

    /**
     * Information about stop
     * @var \App\Model\Stop
     */
    private $stop;

    /**
     * Vehicle identification
     * @var string|null
     */
    private $vehicle;

    /**
     * Displayed destination
     * @var string|null
     */
    private $display;

    /**
     * Estimated time of departure, null if case of no realtime data
     * @var Carbon|null
     */
    private $estimated;

    /**
     * Scheduled time of departure
     * @var Carbon
     */
    private $scheduled;

    public function getLine(): Line
    {
        return $this->line;
    }

    public function setLine(Line $line): void
    {
        $this->line = $line;
    }

    public function getVehicle(): ?string
    {
        return $this->vehicle;
    }

    public function setVehicle(?string $vehicle): void
    {
        $this->vehicle = $vehicle;
    }

    public function getDisplay(): ?string
    {
        return $this->display;
    }

    public function setDisplay(?string $display): void
    {
        $this->display = $display;
    }

    public function getEstimated(): ?Carbon
    {
        return $this->estimated;
    }

    public function setEstimated(?Carbon $estimated): void
    {
        $this->estimated = $estimated;
    }

    public function getScheduled(): Carbon
    {
        return $this->scheduled;
    }

    public function setScheduled(Carbon $scheduled): void
    {
        $this->scheduled = $scheduled;
    }

    public function getStop(): Stop
    {
        return $this->stop;
    }

    public function setStop(Stop $stop): void
    {
        $this->stop = $stop;
    }

    public function getDelay(): ?int
    {
        return $this->getEstimated()
            ? $this->getScheduled()->diffInSeconds($this->getEstimated(), false)
            : null;
    }
}