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
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table('line')]
class LineEntity implements Fillable, Entity
{
    use FillTrait, ReferableEntityTrait, ProviderReferenceTrait, ImportedTrait;

    /**
     * Line symbol, for example '10', or 'A'
     */
    #[ORM\Column(type: 'string', length: 16)]
    private string $symbol;

    /**
     * Line type tram, bus or whatever.
     */
    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    /**
     * Is line considered as fast line?
     */
    #[ORM\Column(type: 'boolean')]
    private bool $fast = false;

    /**
     * Is line considered as night line?
     */
    #[ORM\Column(type: 'boolean')]
    private bool $night = false;

    /**
     * Line operator
     */
    #[ORM\ManyToOne(targetEntity: OperatorEntity::class)]
    private OperatorEntity $operator;

    #[ORM\OneToMany(targetEntity: TrackEntity::class, mappedBy: 'line')]
    private readonly Collection $tracks;

    public function __construct()
    {
        $this->tracks = new ArrayCollection();
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): void
    {
        $this->symbol = $symbol;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function isFast(): bool
    {
        return $this->fast;
    }

    public function setFast(bool $fast): void
    {
        $this->fast = $fast;
    }

    public function isNight(): bool
    {
        return $this->night;
    }

    public function setNight(bool $night): void
    {
        $this->night = $night;
    }

    public function getTracks()
    {
        return $this->tracks;
    }

    /**
     * @return OperatorEntity
     */
    public function getOperator(): ?OperatorEntity
    {
        return $this->operator;
    }

    public function setOperator(OperatorEntity $operator): void
    {
        $this->operator = $operator;
    }
}
