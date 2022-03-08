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

use App\Parser\StreamingConsumer\StreamingConsumer;
use App\Parser\JsonStreamingTokenizer;
use App\Parser\StringStream;
use PHPUnit\Framework\TestCase;

class JsonConstantStreamingTest extends TestCase
{
    public function testTrueValue()
    {
        $stream = new StringStream("true");

        $this->assertSame(true, StreamingConsumer::result($stream->consume(JsonStreamingTokenizer::boolean())));
    }

    public function testFalseValue()
    {
        $stream = new StringStream("false");

        $this->assertSame(false, StreamingConsumer::result($stream->consume(JsonStreamingTokenizer::boolean())));
    }

    public function testNullValue()
    {
        $stream = new StringStream("null");

        $this->assertSame(null, StreamingConsumer::result($stream->consume(JsonStreamingTokenizer::null())));
    }
}
