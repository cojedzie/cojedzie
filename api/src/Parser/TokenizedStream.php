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

use Ds\Deque;

class TokenizedStream implements StreamInterface
{
    use ConsumableTrait;
    private Deque $buffer;
    private \Generator $generator;
    private TokenizedPosition $position;

    public function __construct(
        private StreamInterface $decorated,
        private ParserInterface $parser
    ) {
        $this->buffer    = new Deque();
        $this->generator = ($this->parser)($this->decorated);
        $this->position  = new TokenizedPosition(0);
    }

    public function read(int $max = 1)
    {
        $result = $this->peek($max);

        foreach ($result as $_) {
            $this->buffer->shift(); // discard token
        }

        $this->position = $this->position->advance($result);

        return $result;
    }

    public function peek(int $max = 1)
    {
        $this->fillBuffer($max);

        return $this->buffer->slice(0, $max);
    }

    public function eof(): bool
    {
        return empty($this->buffer) && !$this->generator->valid();
    }

    public function tell(): TokenizedPosition
    {
        return $this->position;
    }

    private function fillBuffer(int $max)
    {
        for (; $this->generator->valid() && count($this->buffer) < $max; $this->generator->next()) {
            $this->buffer->push($this->generator->current());
        }
    }
}
