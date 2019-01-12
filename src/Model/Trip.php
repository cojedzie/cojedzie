<?php

namespace App\Model;

use Tightenco\Collect\Support\Collection;

class Trip implements Referable, Fillable
{
    use ReferableTrait, FillTrait;

    /**
     * Line variant describing trip, for example 'a'
     * @var string|null
     *
     */
    private $variant;

    /**
     * Trip description
     * @var string|null
     */
    private $description;

    /**
     * Line reference
     * @var ?Track
     */
    private $track;

    /**
     * Stops in track
     * @var Collection<ScheduleStop>
     */
    private $schedule;

    /**
     * Track constructor.
     */
    public function __construct()
    {
        $this->setSchedule([]);
    }

    public function getVariant(): ?string
    {
        return $this->variant;
    }

    public function setVariant(?string $variant): void
    {
        $this->variant = $variant;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getTrack(): ?Track
    {
        return $this->track;
    }

    public function setTrack(?Track $track): void
    {
        $this->track = $track;
    }

    public function getSchedule(): Collection
    {
        return $this->schedule;
    }

    public function setSchedule($schedule = [])
    {
        return $this->schedule = collect($schedule);
    }
}