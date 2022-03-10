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

class FileStringStream implements StreamInterface
{
    use PositionTrait, ConsumableTrait;
    private $handle;
    private string $buffer  = "";
    private int $bufferSize = 0;

    public function __construct(string $filename)
    {
        $this->handle   = fopen($filename, 'rb');
        $this->position = new Position();
    }

    public function read(int $max)
    {
        $result = $this->peek($max);
        $this->advance($result, $max);

        $this->buffer = substr($this->buffer, $max);
        $this->bufferSize -= $max;

        return $result;
    }

    public function peek(int $max)
    {
        if ($this->eof()) {
            throw new EndOfStreamException();
        }

        if ($this->bufferSize < $max) {
            $chunk = stream_get_line($this->handle, $max - $this->bufferSize + 1024, PHP_EOL);
            $this->buffer .= $chunk;
            $this->bufferSize += strlen($chunk);
        }

        return $max == 1 ? $this->buffer[0] : substr($this->buffer, 0, $max);
    }

    public function eof(): bool
    {
        return feof($this->handle) && empty($this->buffer);
    }

    public function __destruct()
    {
        fclose($this->handle);
    }
}
