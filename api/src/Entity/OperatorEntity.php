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

#[ORM\Entity]
#[ORM\Table('operator')]
class OperatorEntity implements Fillable, Entity
{
    use ProviderReferenceTrait, FillTrait, ReferableEntityTrait, ImportedTrait;

    /**
     * Describes operator name
     */
    #[ORM\Column(type: 'string')]
    private string $name;

    /**
     * Contact email to operator
     */
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $email = null;

    /**
     * URL of operators page
     */
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $url = null;

    /**
     * Contact phone to operator
     */
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $phone = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }
}
