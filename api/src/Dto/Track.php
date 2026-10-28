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

use Ds\Seq;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

/**
 * @phpstan-consistent-constructor
 */
#[ContentType('vnd.cojedzie.track')]
class Track implements Referable, Fillable, Dto
{
    use ReferableTrait, FillTrait;

    /**
     * Line variant describing track, for example 'a'
     */
    #[OA\Property(example: 'a')]
    private ?string $variant = null;

    /**
     * Track description
     */
    private ?string $description = null;

    /**
     * Line reference
     */
    #[OA\Property(ref: new Model(type: Line::class, groups: ['Default']))]
    private ?Line $line = null;

    /**
     * Stops in track
     * @var Seq<Stop>
     */
    #[OA\Property(type: 'array', items: new OA\Items(ref: new Model(type: Stop::class)))]
    private Seq $stops;

    /**
     * Destination stop of this track
     */
    #[OA\Property(ref: new Model(type: Stop::class))]
    private ?Stop $destination = null;

    public function __construct()
    {
        $this->stops = new Seq();
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

    public function getLine(): ?Line
    {
        return $this->line;
    }

    public function setLine(?Line $line): void
    {
        $this->line = $line;
    }

    public function getStops(): Seq
    {
        return $this->stops;
    }

    public function setStops(iterable $stops = []): void
    {
        $this->stops = new Seq($stops);
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
