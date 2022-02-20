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
use Illuminate\Support\Collection;
use JMS\Serializer\Annotation as Serializer;

class Trip implements Referable, Fillable, DTO
{
    use ReferableTrait, FillTrait;

    /**
     * Line variant describing trip, for example 'a'
     * @Serializer\Type("string")
     *
     */
    private ?string $variant = null;

    /**
     * Trip description
     * @Serializer\Type("string")
     */
    private ?string $description = null;

    /**
     * Line reference
     * @Serializer\Type("App\Model\Track")
     * @SerializeAs({"Default": "Identity"})
     */
    private ?Track $track = null;

    /**
     * Stops in track
     * @Serializer\Type("Collection<App\Model\ScheduledStop>")
     * @var Collection<ScheduledStop>
     */
    private Collection $schedule;

    /**
     * Destination stop of this trip
     */
    private ?Stop $destination = null;

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
