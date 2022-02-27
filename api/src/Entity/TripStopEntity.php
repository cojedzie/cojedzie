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
use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table('trip_stop')]
class TripStopEntity implements Fillable, Referable
{
    use FillTrait, ImportedTrait;

    /**
     * Identifier for stop coming from provider
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: StopEntity::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'stop_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private StopEntity $stop;

    #[ORM\ManyToOne(targetEntity: TripEntity::class, fetch: 'EAGER', inversedBy: 'stops')]
    #[ORM\JoinColumn(name: 'trip_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private TripEntity $trip;

    /**
     * Order in trip
     */
    #[ORM\Column(name: 'sequence', type: 'integer')]
    private int $order;

    /**
     * Arrival time
     */
    #[ORM\Column(type: 'datetime', nullable: false)]
    private Carbon $arrival;

    /**
     * Departure time
     */
    #[ORM\Column(type: 'datetime', nullable: false)]
    private Carbon $departure;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStop(): ?StopEntity
    {
        return $this->stop;
    }

    public function setStop($stop): void
    {
        $this->stop = $stop;
    }

    public function getTrip(): ?TripEntity
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
