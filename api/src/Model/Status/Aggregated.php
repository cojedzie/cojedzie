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

namespace App\Model\Status;

use App\Model\DTO;
use App\Model\Fillable;
use App\Model\FillTrait;
use Illuminate\Support\Collection;
use JMS\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

class Aggregated implements Fillable, DTO
{
    use FillTrait;

    /**
     * Time status for this node.
     *
     * @OA\Property(ref=@Model(type=Time::class))
     */
    #[Serializer\Type(Time::class)]
    private Time $time;

    /**
     * All endpoints defined for this node.
     *
     * @OA\Property(type="array", @OA\Items(ref=@Model(type=Endpoint::class)))
     *
     * @var Collection<Endpoint>
     */
    #[Serializer\Type('Collection<App\Model\Status\Endpoint>')]
    private Collection $endpoints;

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
}
