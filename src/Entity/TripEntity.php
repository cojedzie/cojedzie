<?php

namespace App\Entity;

use App\Model\Fillable;
use App\Model\FillTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("trip")
 */
class TripEntity implements Entity, Fillable
{
    use ReferableEntityTrait, ProviderReferenceTrait, FillTrait;

    /**
     * Operator of the trip
     *
     * @var OperatorEntity
     * @ORM\ManyToOne(targetEntity=OperatorEntity::class)
     */
    private $operator;

    /**
     * Track of the trip
     *
     * @var TrackEntity
     * @ORM\ManyToOne(targetEntity=TrackEntity::class)
     */
    private $track;

    /**
     * Variant of track, for example some alternative route
     *
     * @var ?string
     * @ORM\Column("variant", nullable=true)
     */
    private $variant;

    /**
     * Description of variant
     *
     * @var ?string
     * @ORM\Column("note", nullable=true)
     */
    private $note;

    /**
     * @var Collection<TripStopEntity>
     *
     * @ORM\OneToMany(targetEntity=TripStopEntity::class, fetch="EXTRA_LAZY", mappedBy="trip", cascade={"persist"})
     * @ORM\OrderBy({"order": "ASC"})
     */
    private $stops;

    /**
     * TripEntity constructor.
     */
    public function __construct()
    {
        $this->setStops([]);
    }

    public function getOperator(): OperatorEntity
    {
        return $this->operator;
    }

    public function setOperator(OperatorEntity $operator): void
    {
        $this->operator = $operator;
    }

    public function getTrack(): TrackEntity
    {
        return $this->track;
    }

    public function setTrack(TrackEntity $track): void
    {
        $this->track = $track;
    }

    public function getVariant(): ?string
    {
        return $this->variant;
    }

    public function setVariant(?string $variant): void
    {
        $this->variant = $variant;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    public function getStops(): Collection
    {
        return $this->stops;
    }

    public function setStops(iterable $stops): void
    {
        $this->stops = new ArrayCollection(is_array($stops) ? $stops : iterator_to_array($stops));
    }
}