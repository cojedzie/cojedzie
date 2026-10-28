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
use App\Parser\Exception\StreamOpenException;
use App\Parser\Exception\StreamReadException;

class FileStringStream implements StreamInterface
{
    use StringPositionTrait, ConsumableTrait;
    private $handle;
    private string $buffer  = "";
    private int $bufferSize = 0;

    /**
     * @param float $timeout seconds to wait for an upstream to answer, and for each read from it
     */
    public function __construct(string $filename, float $timeout = 60.0)
    {
        // an unavailable source (e.g. an upstream returning 404) must fail here, not on the first read
        $handle = @fopen($filename, 'rb', false, stream_context_create([
            'http' => [
                'timeout' => $timeout,
            ],
        ]));

        if ($handle === false) {
            throw new StreamOpenException(sprintf('Cannot open "%s": %s', $filename, error_get_last()['message'] ?? 'unknown error'));
        }

        $this->handle   = $handle;
        $this->position = new StringPosition();
    }

    #[\Override]
    public function read(int $max = 1)
    {
        $result = $this->peek($max);
        $this->advance($result, $max);

        $this->buffer = substr($this->buffer, $max);
        $this->bufferSize -= $max;

        return $result;
    }

    #[\Override]
    public function peek(int $max = 1)
    {
        if ($this->eof()) {
            throw new EndOfStreamException();
        }

        if ($this->bufferSize < $max) {
            $chunk = stream_get_line($this->handle, $max - $this->bufferSize + 1024, PHP_EOL);

            if ($chunk === false && stream_get_meta_data($this->handle)['timed_out']) {
                throw new StreamReadException('Reading the stream timed out.');
            }

            $this->buffer .= $chunk;
            $this->bufferSize += strlen($chunk);
        }

        return $max == 1 ? $this->buffer[0] : substr($this->buffer, 0, $max);
    }

    #[\Override]
    public function eof(): bool
    {
        return feof($this->handle) && empty($this->buffer);
    }

    public function __destruct()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }
}
