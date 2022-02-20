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

use Carbon\Carbon;
use JMS\Serializer\Annotation as Serializer;
use OpenApi\Annotations as OA;

class Provider implements Fillable, Referable, DTO
{
    use FillTrait;

    /**
     * Short identifier of provider, ex. "trojmiasto"
     * @OA\Property(example="trojmiasto")
     */
    #[Serializer\Type('string')]
    private string $id;

    /**
     * Full name of the provider, ex. "MZKZG Trójmiasto"
     * @OA\Property(example="MZKZG Trójmiasto")
     */
    #[Serializer\Type('string')]
    private string $name;

    /**
     * Short name of the provider for easier identification, ex. "Trójmiasto" or "Warszawa"
     * @OA\Property(example="Trójmiasto")
     */
    #[Serializer\Type('string')]
    private string $shortName;

    /**
     * Attribution to be presented for this provider, can contain HTML tags.
     * @OA\Property(example="Copyright by XYZ inc.")
     */
    #[Serializer\Type('string')]
    private ?string $attribution = null;

    /**
     * Time when data was last synchronized with this provider.
     */
    #[Serializer\Type('Carbon')]
    private ?Carbon $lastUpdate = null;

    /**
     * Location of provider centre of interest.
     */
    private Location $location;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getShortName(): string
    {
        return $this->shortName;
    }

    public function setShortName(string $shortName): void
    {
        $this->shortName = $shortName;
    }

    public function getAttribution(): ?string
    {
        return $this->attribution;
    }

    public function setAttribution(?string $attribution): void
    {
        $this->attribution = $attribution;
    }

    public function getLastUpdate(): ?Carbon
    {
        return $this->lastUpdate;
    }

    public function setLastUpdate(?Carbon $lastUpdate): void
    {
        $this->lastUpdate = $lastUpdate;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function setLocation(Location $location): void
    {
        $this->location = $location;
    }
}
