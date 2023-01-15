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

class Destination implements Fillable, Dto
{
    use FillTrait;

    /**
     * Stop associated with destination.
     */
    private Stop $stop;

    /**
     * @OA\Property(type="array", @OA\Items(ref=@Model(type=Line::class, groups={"Default"})))
     * @var Collection<Line>
     */
    private Collection $lines;

    public function __construct()
    {
        $this->lines = collect();
    }

    public function getStop(): Stop
    {
        return $this->stop;
    }

    public function setStop(Stop $stop): void
    {
        $this->stop = $stop;
    }

    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function setLines(iterable $lines): void
    {
        $this->lines = collect($lines);
    }
}
