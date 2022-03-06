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

use App\Parser\Consumer\ConsumerInterface;
use App\Parser\Exception\EndOfStreamException;

class StringStream implements StreamInterface
{
    private Position $position;

    public function __construct(
        private string $string
    ) {
        $this->position = new Position();
    }

    public function read(int $max): string
    {
        if ($this->eof()) {
            throw new EndOfStreamException();
        }

        $slice = $this->peek($max);
        $this->advance($slice);

        return $slice;
    }

    public function peek(int $max): string
    {
        if ($this->eof()) {
            throw new EndOfStreamException();
        }

        return mb_substr($this->string, $this->position->offset, $max);
    }

    public function consume(ConsumerInterface $consumer): \Generator
    {
        return $consumer($this);
    }

    public function skip(ConsumerInterface $consumer): \Generator
    {
        iterator_to_array($generator = $this->consume($consumer));
        return $generator;
    }

    public function eof(): bool
    {
        return $this->position->offset >= mb_strlen($this->string);
    }

    public function tell(): Position
    {
        return $this->position;
    }

    private function advance(string $slice)
    {
        $this->position = new Position(
            offset: min(
                $this->position->offset + mb_strlen($slice),
                mb_strlen($this->string)
            )
        );
    }
}
