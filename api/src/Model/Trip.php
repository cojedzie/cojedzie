<?php

namespace App\Model;

use App\Serialization\SerializeAs;
use Illuminate\Support\Collection;
use JMS\Serializer\Annotation as Serializer;

class Trip implements Referable, Fillable, DTO
{
    use ReferableTrait, FillTrait;

    /**
     * Line variant describing trip, for example 'a'
     * @Serializer\Type("string")
     * @var string|null
     *
     */
    private $variant;

    /**
     * Trip description
     * @var string|null
     * @Serializer\Type("string")
     */
    private $description;

    /**
     * Line reference
     * @var ?Track
     * @Serializer\Type("App\Model\Track")
     * @SerializeAs({"Default": "Identity"})
     */
    private $track;

    /**
     * Stops in track
     * @Serializer\Type("Collection<App\Model\ScheduledStop>")
     * @var Collection<ScheduledStop>
     */
    private $schedule;

    /**
     * Destination stop of this trip
     * @var Stop|null
     */
    private $destination;

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

    public function getDestination(): ?Stop
    {
        return $this->destination;
    }

    public function setDestination(?Stop $destination): void
    {
        $this->destination = $destination;
    }
}
