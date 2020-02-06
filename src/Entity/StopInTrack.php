<?php

namespace App\Entity;

use App\Model\Fillable;
use App\Model\FillTrait;
use App\Model\Referable;
use App\Model\ReferableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("track_stop", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="stop_in_track_idx", columns={"stop_id", "track_id", "sequence"})
 * })
 */
class StopInTrack implements Fillable, Referable
{
    use FillTrait, ReferableEntityTrait;

    /**
     * Identifier for stop coming from provider
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=StopEntity::class, fetch="EAGER")
     */
    private $stop;

    /**
     * @ORM\ManyToOne(targetEntity=TrackEntity::class, fetch="EAGER", inversedBy="stopsInTrack")
     */
    private $track;

    /**
     * Order in track
     * @var int
     * @ORM\Column(name="sequence", type="integer")
     */
    private $order;

    public function getStop(): StopEntity
    {
        return $this->stop;
    }

    public function setStop(StopEntity $stop): void
    {
        $this->stop = $stop;
    }

    public function getTrack(): TrackEntity
    {
        return $this->track;
    }

    public function setTrack(TrackEntity $track): void
    {
        $this->track = $track;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): void
    {
        $this->order = $order;
    }
}
