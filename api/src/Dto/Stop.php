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

namespace App\Dto;

use Illuminate\Support\Collection;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @package App\Model
 */
#[ContentType('vnd.cojedzie.stop')]
class Stop implements Referable, Fillable, Dto
{
    use FillTrait, ReferableTrait;

    /**
     * Stop name
     *
     * @OA\Property(example="Jasień PKM")
     */
    private string $name;

    /**
     * Optional stop description, should not be longer than 255 chars.
     */
    private ?string $description = null;

    /**
     * Optional stop variant - for example number of shed.
     *
     * @OA\Property(example="01")
     */
    private ?string $variant = null;

    /**
     * Optional stop location in form of latitude and longitude
     */
    private ?Location $location = null;

    /**
     * True if stop is available only on demand
     *
     * @OA\Property(example=false)
     */
    private bool $onDemand = false;

    /**
     * Name of group that this stop is part of.
     *
     * @OA\Property(example="Jasień PKM")
     */
    private ?string $group = null;

    /**
     * Collection of possible destination stops.
     *
     * @OA\Property(type="array", @OA\Items(ref=@Model(type=Destination::class, groups={"default"})))
     *
     * @var Collection<Destination>
     */
    #[Groups(['embed:destinations'])]
    private Collection $destinations;

    public function __construct()
    {
        $this->destinations = collect();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getVariant(): ?string
    {
        return $this->variant;
    }

    public function setVariant(?string $variant): void
    {
        $this->variant = $variant;
    }

    public function isOnDemand(): bool
    {
        return $this->onDemand;
    }

    public function setOnDemand(bool $onDemand): void
    {
        $this->onDemand = $onDemand;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function setLocation(?Location $location): void
    {
        $this->location = $location;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function setGroup(?string $group): void
    {
        $this->group = $group;
    }

    public function getDestinations(): Collection
    {
        return $this->destinations;
    }

    public function setDestinations(Collection $destinations): void
    {
        $this->destinations = $destinations;
    }
}
