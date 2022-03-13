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

use App\Parser\Exception\EndOfStreamException;

class StringStream implements StreamInterface
{
    use ConsumableTrait, StringPositionTrait;

    public function __construct(
        private string $string
    ) {
        $this->position = new StringPosition();
    }

    public function read(int $max = 1): string
    {
        if ($this->eof()) {
            throw new EndOfStreamException();
        }

        $slice = $this->peek($max);
        $this->advance($slice, $max);

        return $slice;
    }

    public function peek(int $max = 1): string
    {
        if ($this->eof()) {
            throw new EndOfStreamException();
        }

        return mb_substr($this->string, $this->position->offset, $max);
    }

    public function eof(): bool
    {
        return $this->position->offset >= mb_strlen($this->string);
    }
}
