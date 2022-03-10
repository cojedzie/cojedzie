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

class GeneratorStringStream implements StreamInterface
{
    use PositionTrait, ConsumableTrait;
    private string $buffer = "";
    private int $offset    = 0;

    public function __construct(
        private \Generator $generator
    ) {
        $this->position = new Position();
    }

    public function read(int $max)
    {
        $result = $this->peek($max);
        $this->advance($result, $max);

        $this->buffer = substr($this->buffer, $max);

        return $result;
    }

    public function peek(int $max)
    {
        $this->fillBuffer($max);

        if ($this->eof()) {
            throw new EndOfStreamException();
        }

        return substr($this->buffer, $this->offset, $max);
    }

    public function eof(): bool
    {
        return empty($this->buffer) && !$this->generator->valid();
    }

    private function fillBuffer(int $length)
    {
        for (; strlen($this->buffer) < $length && $this->generator->valid(); $this->generator->next()) {
            $this->buffer .= $this->generator->current();
        }
    }
}
