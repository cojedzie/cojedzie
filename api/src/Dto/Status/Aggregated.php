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
use Ds\Seq;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

class Aggregated implements Fillable, Dto
{
    use FillTrait;

    public function __construct(
        /**
         * Time status for this node.
         */
        #[OA\Property(ref: new Model(type: Time::class))]
        private Time $time,

        /**
         * Version of the software on this ndoe.
         */
        #[OA\Property(ref: new Model(type: Version::class))]
        private Version $version,

        /**
         * All endpoints defined for this node.
         *
         * @var Seq<Endpoint>
         */
        #[OA\Property(type: 'array', items: new OA\Items(ref: new Model(type: Endpoint::class)))]
        private Seq $endpoints,
    ) {
    }

    public function getEndpoints(): Seq
    {
        return $this->endpoints;
    }

    public function setEndpoints(iterable $endpoints): void
    {
        $this->endpoints = new Seq($endpoints);
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
