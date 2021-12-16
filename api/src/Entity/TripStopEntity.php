<?php
/*
 * Copyright (C) 2021 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Entity;

use App\Model\Fillable;
use App\Model\FillTrait;
use App\Model\Referable;
use App\Model\Trip;
use App\Service\IdUtils;
use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Tests\Fixtures\Discriminator\Car;

/**
 * @ORM\Entity
 * @ORM\Table("trip_stop")
 */
class TripStopEntity implements Fillable, Referable
{
    use FillTrait, ReferableEntityTrait;

    /**
     * Identifier for stop coming from provider
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var StopEntity
     * @ORM\ManyToOne(targetEntity=StopEntity::class, fetch="EAGER")
     * @ORM\JoinColumn(name="stop_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $stop;

    /**
     * @var TripEntity
     * @ORM\ManyToOne(targetEntity=TripEntity::class, fetch="EAGER", inversedBy="stops")
     * @ORM\JoinColumn(name="trip_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $trip;

    /**
     * Order in trip
     * @var int
     *
     * @ORM\Column(name="sequence", type="integer")
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
