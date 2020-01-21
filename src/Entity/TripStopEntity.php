<?php

namespace App\Entity;

use App\Model\Fillable;
use App\Model\FillTrait;
use App\Model\Trip;
use App\Service\IdUtils;
use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Tests\Fixtures\Discriminator\Car;

/**
 * @ORM\Entity
 * @ORM\Table("trip_stop")
 */
class TripStopEntity implements Fillable
{
    use FillTrait;

    /**
     * @var StopEntity
     * @ORM\ManyToOne(targetEntity=StopEntity::class, fetch="EAGER")
     * @ORM\Id
     */
    private $stop;

    /**
     * @var TripEntity
     * @ORM\ManyToOne(targetEntity=TripEntity::class, fetch="EAGER", inversedBy="stops")
     * @ORM\Id
     */
    private $trip;

    /**
     * Order in trip
     * @var int
     *
     * @ORM\Column(name="sequence", type="integer")
     * @ORM\Id
     */
    private $order;

    /**
     * Arrival time
     * @var Carbon
     *
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $arrival;

    /**
     * Departure time
     * @var Carbon
     *
     * @ORM\Column(type="datetime", nullable=false)
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

    public function getTrip()
    {
        return $this->trip;
    }

    public function setTrip($trip): void
    {
        $this->trip = $trip;
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
        return Carbon::instance($this->departure)->tz('UTC');
    }

    public function setDeparture(Carbon $departure): void
    {
        $this->departure = $departure;
    }
}