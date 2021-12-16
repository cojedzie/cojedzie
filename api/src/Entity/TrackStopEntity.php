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
use App\Model\Referable;
use App\Model\ReferableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("track_stop", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="stop_in_track_idx", columns={"stop_id", "track_id", "sequence"})
 * })
 */
class TrackStopEntity implements Fillable, Referable
{
    use FillTrait, ReferableEntityTrait;

    /**
     * Identifier for stop coming from provider
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=StopEntity::class, fetch="EAGER")
     * @ORM\JoinColumn(name="stop_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $stop;

    /**
     * @ORM\ManyToOne(targetEntity=TrackEntity::class, fetch="EAGER", inversedBy="stopsInTrack")
     * @ORM\JoinColumn(name="track_id", referencedColumnName="id", onDelete="CASCADE")
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
