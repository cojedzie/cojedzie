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

namespace App\Model;

use App\Serialization\SerializeAs;
use Carbon\Carbon;
use JMS\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

class Departure implements Fillable, DTO
{
    use FillTrait;

    /**
     * Unique identifier of departure, can be meaningless.
     */
    #[Serializer\Type('string')]
    private ?string $key = null;

    /**
     * Information about line.
     * @OA\Property(ref=@Model(type=Line::class, groups={"Default"}))
     *
     */
    #[Serializer\Type(Line::class)]
    #[SerializeAs(['Default' => 'Default'])]
    private Line $line;

    /**
     * Information about line.
     * @OA\Property(ref=@Model(type=Track::class, groups={"Identity"}))
     */
    #[Serializer\Type(Track::class)]
    #[SerializeAs(['Default' => 'Identity'])]
    private ?Track $track = null;

    /**
     * Information about line.
     * @OA\Property(ref=@Model(type=Trip::class, groups={"Identity"}))
     */
    #[Serializer\Type(Trip::class)]
    #[SerializeAs(['Default' => 'Identity'])]
    private ?Trip $trip = null;

    /**
     * Information about stop.
     */
    #[Serializer\Type(Stop::class)]
    private Stop $stop;

    /**
     * Vehicle identification.
     */
    #[Serializer\Type(Vehicle::class)]
    private ?Vehicle $vehicle = null;

    /**
     * Displayed destination.
     * @OA\Property(example="Łostowice Świętokrzyska")
     */
    #[Serializer\Type('string')]
    private ?string $display = null;

    /**
     * Estimated time of departure, null if case of no realtime data.
     * @OA\Property(type="string", format="date-time")
     */
    #[Serializer\Type('Carbon')]
    private ?Carbon $estimated = null;

    /**
     * Scheduled time of departure.
     * @OA\Property(type="string", format="date-time")
     */
    #[Serializer\Type('Carbon')]
    private Carbon $scheduled;

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(?string $key): void
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

    public function getTrack(): ?Track
    {
        return $this->track;
    }

    public function setTrack(?Track $track): void
    {
        $this->track = $track;
    }

    public function getTrip(): ?Trip
    {
        return $this->trip;
    }

    public function setTrip(?Trip $trip): void
    {
        $this->trip = $trip;
    }

    /**
     * @OA\Property(type="int")
     */
    #[Serializer\VirtualProperty]
    #[Serializer\Type('int')]
    public function getDelay() : ?int
    {
        return $this->getEstimated()
            ? $this->getScheduled()->diffInSeconds($this->getEstimated(), false)
            : null;
    }
}
