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

use JMS\Serializer\Annotation as Serializer;
use OpenApi\Annotations as OA;

class Location implements DTO
{
    /**
     * Locations longitude.
     */
    #[Serializer\Type('float')]
    #[Serializer\SerializedName('lng')]
    private $longitude;

    /**
     * Locations latitude.
     * @OA\Property()
     */
    #[Serializer\Type('float')]
    #[Serializer\SerializedName('lat')]
    private $latitude;

    public function __construct(float $longitude = 0.0, float $latitude = 0.0)
    {
        $this->set($longitude, $latitude);
    }

    public function getLongitude()
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): void
    {
        $this->longitude = $longitude;
    }

    public function getLatitude()
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): void
    {
        $this->latitude = $latitude;
    }

    public function set(float $longitude, float $latitude)
    {
        $this->setLongitude($longitude);
        $this->setLatitude($latitude);
    }
}
