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

namespace App\Tests\JsonStreamingTokenizer;

use App\Parser\Json\JsonStreamingTokenizer;
use App\Parser\StringStream;
use PHPUnit\Framework\TestCase;

class JsonNumberStreamingTest extends TestCase
{
    public function testZeroWithoutFraction()
    {
        $stream = new StringStream('0');

        $this->assertSame(0.0, $stream->consume(JsonStreamingTokenizer::number()));
    }

    public function testSimpleInteger()
    {
        $stream = new StringStream('123');

        $this->assertSame(123.0, $stream->consume(JsonStreamingTokenizer::number()));
    }

    public function testSimpleNegativeInteger()
    {
        $stream = new StringStream('-123');

        $this->assertSame(-123.0, $stream->consume(JsonStreamingTokenizer::number()));
    }

    public function testSimpleFraction()
    {
        $stream = new StringStream('21.37');

        $this->assertSame(21.37, $stream->consume(JsonStreamingTokenizer::number()));
    }

    public function testSimpleNegativeFraction()
    {
        $stream = new StringStream('-21.37');

        $this->assertSame(-21.37, $stream->consume(JsonStreamingTokenizer::number()));
    }

    public function testSimpleNegativeFractionWithZero()
    {
        $stream = new StringStream('-0.123');

        $this->assertSame(-0.123, $stream->consume(JsonStreamingTokenizer::number()));
    }

    public function testSimpleNegativeFractionWithLeadingZeroes()
    {
        $stream = new StringStream('-0.00123');

        $this->assertSame(-0.00123, $stream->consume(JsonStreamingTokenizer::number()));
    }
}
