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

use App\Dto\Fillable;
use App\Dto\FillTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table('trip')]
#[ORM\Index(columns: ['valid_to'])]
#[ORM\Index(name: 'trip_service_date_idx', columns: ['service_date'])]
// answers the first and the last service day of a provider without reading its trips
#[ORM\Index(name: 'trip_provider_service_date_idx', columns: ['provider_id', 'service_date'])]
class TripEntity implements Entity, Fillable, ValidityAwareInterface
{
    use ReferableEntityTrait, ProviderReferenceTrait, FillTrait, ImportedTrait, ValidityTrait;

    /**
     * Operator of the trip
     */
    #[ORM\ManyToOne(targetEntity: OperatorEntity::class)]
    private OperatorEntity $operator;

    /**
     * Track of the trip
     */
    #[ORM\ManyToOne(targetEntity: TrackEntity::class)]
    #[ORM\JoinColumn(name: 'track_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private TrackEntity $track;

    /**
     * Variant of track, for example some alternative route
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $variant = null;

    /**
     * Description of variant
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $note = null;

    /**
     * Service day the trip belongs to, in the provider's timezone; its runs may go past midnight
     */
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $serviceDate;

    /**
     * @var Collection<TripStopEntity>
     */
    #[ORM\OneToMany(targetEntity: TripStopEntity::class, fetch: 'EXTRA_LAZY', mappedBy: 'trip', cascade: ['persist'])]
    #[ORM\OrderBy(['order' => 'ASC'])]
    private Collection $stops;

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

    public function getServiceDate(): \DateTimeImmutable
    {
        return $this->serviceDate;
    }

    public function setServiceDate(\DateTimeImmutable $serviceDate): void
    {
        $this->serviceDate = $serviceDate;
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
