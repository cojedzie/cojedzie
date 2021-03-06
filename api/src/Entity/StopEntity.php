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
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(readOnly: true)]
#[ORM\Table('stop', indexes: [new ORM\Index(name: 'group_idx', columns: ['group_name'])])]
#[ORM\Index(name: 'group_idx', columns: ['group_name'])]
class StopEntity implements Entity, Fillable
{
    use FillTrait, ReferableEntityTrait, ProviderReferenceTrait, ImportedTrait;

    /**
     * Identifier for stop coming from provider
     */
    #[ORM\Column(type: 'string')]
    #[ORM\Id]
    private string $id;

    /**
     * Stop name
     * @var string
     */
    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    /**
     * Stop group name
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true, name: 'group_name')]
    private ?string $group = null;

    /**
     * Optional stop description, should not be longer than 255 chars
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $description = null;

    /**
     * Optional stop variant - for example number of shed
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $variant = null;

    /**
     * Latitude of stop
     */
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $latitude = null;

    /**
     * Longitude of stop
     */
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $longitude = null;

    /**
     * True if stop is available only on demand
     */
    #[ORM\Column(type: 'boolean')]
    private bool $onDemand = false;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function setGroup(?string $group): void
    {
        $this->group = $group;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getVariant(): ?string
    {
        return $this->variant;
    }

    public function setVariant(?string $variant): void
    {
        $this->variant = $variant;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): void
    {
        $this->latitude = $latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): void
    {
        $this->longitude = $longitude;
    }

    public function isOnDemand(): bool
    {
        return $this->onDemand;
    }

    public function setOnDemand(bool $onDemand): void
    {
        $this->onDemand = $onDemand;
    }
}
