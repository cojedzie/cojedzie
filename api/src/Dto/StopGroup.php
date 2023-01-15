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

#[ContentType('vnd.cojedzie.trip')]
class StopGroup implements Dto
{
    /**
     * Name of stop group.
     * @OA\Property(example="Jasień PKM")
     */
    private string $name;

    /**
     * All stops in group.
     * @var Collection<Stop>
     * @OA\Property(
     *     type="array",
     *     @OA\Items(ref=@Model(type=Stop::class, groups={"Default", "WithDestinations"}))
     * )
     */
    private Collection $stops;

    public function __construct()
    {
        $this->stops = new Collection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setStops($stops)
    {
        $this->stops = new Collection($stops);
    }

    public function getStops(): Collection
    {
        return $this->stops;
    }
}
