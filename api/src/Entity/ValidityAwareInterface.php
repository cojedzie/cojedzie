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

/**
 * Entity that is versioned by the time range in which it is valid.
 *
 * Both bounds are inclusive. A missing bound means the range is open on that side.
 */
interface ValidityAwareInterface
{
    public function getValidFrom(): ?Carbon;

    public function setValidFrom(?Carbon $validFrom): void;

    public function getValidTo(): ?Carbon;

    public function setValidTo(?Carbon $validTo): void;

    public function isValidAt(\DateTimeInterface $at): bool;
}
