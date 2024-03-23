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
use App\Parser\StringStream;
use PHPUnit\Framework\TestCase;

class StringStreamTest extends TestCase
{
    public function testStreamAllowsReading()
    {
        $stream = new StringStream('1234567890');

        $this->assertSame('123', $stream->read(3));
        $this->assertSame('456', $stream->read(3));
    }

    public function testStreamAllowsPeekingWithoutReading()
    {
        $stream = new StringStream('1234567890');

        $this->assertSame('123', $stream->peek(3));
        $this->assertSame('123', $stream->peek(3));
        $this->assertSame('123', $stream->read(3));
    }

    public function testStreamReportsEndOfStreamCorrectly()
    {
        $stream = new StringStream('123');

        $this->assertSame('123', $stream->read(3));
        $this->assertTrue($stream->eof());
    }

    public function testStreamDoesNotAllowToReadAfterEof()
    {
        $stream = new StringStream('123');

        $this->assertSame('123', $stream->read(3));
        $this->expectException(EndOfStreamException::class);

        $stream->read(1);
    }

    public function testStreamDoesNotAllowToPeekAfterEof()
    {
        $stream = new StringStream('123');

        $this->assertSame('123', $stream->read(3));
        $this->expectException(EndOfStreamException::class);

        $stream->peek(1);
    }

    public function testStreamCanTellPosition()
    {
        $stream = new StringStream('1234567890');

        $stream->read(3);
        $position = $stream->tell();
        $this->assertSame(3, $position->offset);

        $stream->read(3);
        $position = $stream->tell();
        $this->assertSame(6, $position->offset);
    }
}
