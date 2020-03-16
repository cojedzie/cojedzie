<?php

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
