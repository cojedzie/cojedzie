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

namespace App\Dto\Status;

use App\Dto\Dto;
use App\Dto\Fillable;
use App\Dto\FillTrait;
use Illuminate\Support\Collection;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

class Aggregated implements Fillable, Dto
{
    use FillTrait;

    public function __construct(
        /**
         * Time status for this node.
         *
         * @OA\Property(ref=@Model(type=Time::class))
         */
        private Time $time,

        /**
         * Version of the software on this ndoe.
         *
         * @OA\Property(ref=@Model(type=Version::class))
         */
        private Version $version,

        /**
         * All endpoints defined for this node.
         *
         * @OA\Property(type="array", @OA\Items(ref=@Model(type=Endpoint::class)))
         *
         * @var Collection<Endpoint>
         */
        private Collection $endpoints,
    ) {
    }

    public function getEndpoints(): Collection
    {
        return $this->endpoints;
    }

    public function setEndpoints(Collection $endpoints): void
    {
        $this->endpoints = $endpoints;
    }

    public function getTime(): Time
    {
        return $this->time;
    }

    public function setTime(Time $time): void
    {
        $this->time = $time;
    }

    public function getVersion(): Version
    {
        return $this->version;
    }

    public function setVersion(Version $version): void
    {
        $this->version = $version;
    }
}
