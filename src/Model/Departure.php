<?php

namespace App\Model;

use Carbon\Carbon;
use JMS\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

class Departure implements Fillable
{
    use FillTrait;

    /**
     * Unique identifier of departure, can be meaningless.
     * @var string
     * @Serializer\Type("string")
     */
    private $key;

    /**
     * Information about line.
     * @var Line
     * @Serializer\Type(Line::class)
     * @SWG\Property(ref=@Model(type=Line::class, groups={"Default"}))
     *
     */
    private $line;

    /**
     * Information about stop.
     * @var Stop
     * @Serializer\Type(Stop::class)
     */
    private $stop;

    /**
     * Vehicle identification.
     * @var Vehicle|null
     * @Serializer\Type(Vehicle::class)
     */
    private $vehicle;

    /**
     * Displayed destination.
     * @var string|null
     * @Serializer\Type("string")
     * @SWG\Property(example="Łostowice Świętokrzyska")
     */
    private $display;

    /**
     * Estimated time of departure, null if case of no realtime data.
     * @var Carbon|null
     * @Serializer\Type("Carbon")
     * @SWG\Property(type="string", format="date-time")
     */
    private $estimated;

    /**
     * Scheduled time of departure.
     * @var Carbon
     * @Serializer\Type("Carbon")
     * @SWG\Property(type="string", format="date-time")
     */
    private $scheduled;

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getLine(): Line
    {
        return $this->line;
    }

    public function setLine(Line $line): void
    {
        $this->line = $line;
    }

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }

    public function setVehicle(?Vehicle $vehicle): void
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

    public function getDeparture(): Carbon
    {
        return $this->estimated ?? $this->scheduled;
    }

    public function getStop(): Stop
    {
        return $this->stop;
    }

    public function setStop(Stop $stop): void
    {
        $this->stop = $stop;
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\Type("int")
     * @SWG\Property(type="int")
     */
    public function getDelay(): ?int
    {
        return $this->getEstimated()
            ? $this->getScheduled()->diffInSeconds($this->getEstimated(), false)
            : null;
    }
}
