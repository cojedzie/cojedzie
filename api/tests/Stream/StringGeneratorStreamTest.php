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

namespace App\Tests\Stream;

use App\Parser\Exception\EndOfStreamException;
use App\Parser\GeneratorStringStream;
use PHPUnit\Framework\TestCase;

class StringGeneratorStreamTest extends TestCase
{
    public function testStreamCanBeReadContinuously()
    {
        $stream = $this->createStream(['12', '3456', '789']);

        $this->assertEquals('123', $stream->read(3));
        $this->assertEquals('456', $stream->read(3));
        $this->assertEquals('789', $stream->read(3));
    }

    public function testStreamCanBePeekedContinuously()
    {
        $stream = $this->createStream(['12', '3456', '789']);

        $this->assertEquals('123', $stream->peek(3));
        $this->assertEquals('123', $stream->read(3));
        $this->assertEquals('45678', $stream->peek(5));
    }

    public function testStreamCorrectlyDetectsEof()
    {
        $stream = $this->createStream(['12', '345']);

        $stream->peek(5);
        $this->assertFalse($stream->eof());
        $stream->read(5);
        $this->assertTrue($stream->eof());
    }

    public function testStreamDoesNotAllowToReadAfterEof()
    {
        $stream = $this->createStream(['1', '2', '3']);

        $this->assertEquals('123', $stream->read(3));
        $this->expectException(EndOfStreamException::class);

        $stream->read(1);
    }

    public function testStreamDoesNotAllowToPeekAfterEof()
    {
        $stream = $this->createStream(['1', '2', '3']);

        $this->assertEquals('123', $stream->read(3));
        $this->expectException(EndOfStreamException::class);

        $stream->peek(1);
    }

    public function testStreamCanTellPosition()
    {
        $stream = $this->createStream(['12', '3456', '789']);

        $stream->read(3);
        $position = $stream->tell();
        $this->assertEquals(3, $position->offset);

        $stream->read(3);
        $position = $stream->tell();
        $this->assertEquals(6, $position->offset);
    }

    private function createGeneratorFromChunks(array $chunks)
    {
        yield from $chunks;
    }

    private function createStream(array $chunks)
    {
        return new GeneratorStringStream($this->createGeneratorFromChunks($chunks));
    }
}
