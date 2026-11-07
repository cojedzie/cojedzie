<?php
/*
 * Copyright (C) 2026 Kacper Donat
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

use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;

/**
 * @see ValidityAwareInterface
 */
trait ValidityTrait
{
    #[ORM\Column(name: 'valid_from', type: 'datetime', nullable: true)]
    private ?Carbon $validFrom = null;

    #[ORM\Column(name: 'valid_to', type: 'datetime', nullable: true)]
    private ?Carbon $validTo = null;

    public function getValidFrom(): ?Carbon
    {
        return $this->validFrom;
    }

    public function setValidFrom(?Carbon $validFrom): void
    {
        $this->validFrom = $validFrom;
    }

    public function getValidTo(): ?Carbon
    {
        return $this->validTo;
    }

    public function setValidTo(?Carbon $validTo): void
    {
        $this->validTo = $validTo;
    }

    public function isValidAt(\DateTimeInterface $at): bool
    {
        return ($this->validFrom === null || $this->validFrom <= $at)
            && ($this->validTo === null || $this->validTo >= $at);
    }
}
