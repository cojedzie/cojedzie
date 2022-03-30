<?php
/*
 * Copyright (C) 2022 Kacper Donat
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

namespace App\Filter\Modifier;

use JetBrains\PhpStorm\Pure;

class LimitModifier implements Modifier
{
    #[Pure]
    public function __construct(
        private readonly int $offset = 0,
        private readonly ?int $count = null
    ) {
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    #[Pure]
    public static function count(int $count): self
    {
        return new self(0, $count);
    }
}
