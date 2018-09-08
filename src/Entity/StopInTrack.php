<?php

namespace App\Entity;

use App\Model\Fillable;
use App\Model\FillTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("track_stop")
 */
class StopInTrack implements Fillable
{
    use FillTrait;

    /**
     * @ORM\ManyToOne(targetEntity=StopEntity::class, fetch="EAGER")
     * @ORM\Id
     */
    private $stop;

    /**
     * @ORM\ManyToOne(targetEntity=TrackEntity::class, fetch="EAGER")
     * @ORM\Id
     */
    private $track;

    /**
     * Order in track
     * @var int
     *
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