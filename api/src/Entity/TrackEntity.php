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

namespace App\Entity;

use App\Model\Fillable;
use App\Model\FillTrait;
use App\Service\IterableUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kadet\Functional\Transforms as t;

/**
 * @ORM\Entity
 * @ORM\Table("track")
 */
class TrackEntity implements Entity, Fillable
{
    use ReferableEntityTrait, FillTrait, ProviderReferenceTrait;

    /**
     * Line variant describing track, for example 'a'
     * @var string|null
     *
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    private $variant;

    /**
     * Track description
     * @var string|null
     *
     * @ORM\Column(type="string", length=256, nullable=true)
     */
    private $description;

    /**
     * Line reference
     *
     * @var LineEntity
     *
     * @ORM\ManyToOne(targetEntity=LineEntity::class, fetch="EAGER", inversedBy="tracks")
     * @ORM\JoinColumn(name="line_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $line;

    /**
     * Stops in track
     *
     * @var TrackStopEntity[]|Collection
     * @ORM\OneToMany(targetEntity=TrackStopEntity::class, fetch="LAZY", mappedBy="track", cascade={"persist"})
     * @ORM\OrderBy({"order": "ASC"})
     */
    private $stopsInTrack;

    /**
     * Final stop in this track.
     *
     * @var TrackStopEntity
     * @ORM\OneToOne(targetEntity=TrackStopEntity::class, fetch="LAZY")
     * @ORM\JoinColumn(name="final_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $final;

    /**
     * Track constructor.
     */
    public function __construct()
    {
        $this->setStopsInTrack([]);
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

    public function getLine(): LineEntity
    {
        return $this->line;
    }

    public function setLine(LineEntity $line): void
    {
        $this->line = $line;
    }

    /**
     * @return Collection
     */
    public function getStopsInTrack(): Collection
    {
        return $this->stopsInTrack;
    }

    /**
     * @param iterable $stopsInTrack
     */
    public function setStopsInTrack(iterable $stopsInTrack): void
    {
        $this->stopsInTrack = IterableUtils::toArrayCollection($stopsInTrack);

        $this->final = $this->stopsInTrack->last();
    }

    public function getFinal(): TrackStopEntity
    {
        return $this->final;
    }
}
