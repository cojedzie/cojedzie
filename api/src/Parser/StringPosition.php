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

namespace App\Parser;

class StringPosition implements PositionInterface
{
    public function __construct(
        public readonly int $offset = 0,
        public readonly int $line = 1,
        public readonly int $column = 1,
    ) {
    }

    public function advance($slice, int $length = null)
    {
        $length = $length ?: strlen((string) $slice);

        if ($length === 1) {
            $nl = $slice === "\n";
            return new StringPosition(
                offset: $this->offset + $length,
                line: $this->line + $nl,
                column: $nl ? 1 : $this->column + 1
            );
        } else {
            $lines = preg_split('/\R/', (string) $slice);
            $last  = end($lines);

            return new StringPosition(
                offset: $this->offset + $length,
                line: $this->line + count($lines) - 1,
                column: count($lines) > 1 ? 1 + strlen((string) $last) : $this->column + strlen((string) $last),
            );
        }
    }

    public function __toString(): string
    {
        return "{$this->line}:{$this->column}";
    }
}
