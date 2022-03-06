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

use App\Parser\Consumer\Consumer;
use App\Parser\JsonStreamingTokenizer;
use App\Parser\StringStream;
use PHPUnit\Framework\TestCase;

class JsonStringStreamingTest extends TestCase
{
    public function testSimpleString()
    {
        $stream = new StringStream('"foo"');

        $this->assertSame("foo", Consumer::result($stream->consume(JsonStreamingTokenizer::string())));
    }

    public function testEmptyString()
    {
        $stream = new StringStream('""');

        $this->assertSame("", Consumer::result($stream->consume(JsonStreamingTokenizer::string())));
    }

    public function testStringWithEscapedQuote()
    {
        $stream = new StringStream('"\""');

        $this->assertSame('"', Consumer::result($stream->consume(JsonStreamingTokenizer::string())));
    }

    public function testStringWithEscapedBackslash()
    {
        $stream = new StringStream('"\\\\"');

        $this->assertSame('\\', Consumer::result($stream->consume(JsonStreamingTokenizer::string())));
    }

    public function testStringWithEscapedSlash()
    {
        $stream = new StringStream('"a\/b"');

        $this->assertSame('a/b', Consumer::result($stream->consume(JsonStreamingTokenizer::string())));
    }

    public function testStringWithEscapedTab()
    {
        $stream = new StringStream('"a\tb"');

        $this->assertSame("a\tb", Consumer::result($stream->consume(JsonStreamingTokenizer::string())));
    }

    public function testStringWithNewLine()
    {
        $stream = new StringStream('"a\nb"');

        $this->assertSame("a\nb", Consumer::result($stream->consume(JsonStreamingTokenizer::string())));
    }

    public function testStringWithCarriageReturn()
    {
        $stream = new StringStream('"a\rb"');

        $this->assertSame("a\rb", Consumer::result($stream->consume(JsonStreamingTokenizer::string())));
    }

    public function testStringWithLinefeed()
    {
        $stream = new StringStream('"a\fb"');

        $this->assertSame("a\fb", Consumer::result($stream->consume(JsonStreamingTokenizer::string())));
    }

    public function testStringWithUnicodeEncodedCharacter()
    {
        $stream = new StringStream('"\u02da"');

        $this->assertSame("˚", Consumer::result($stream->consume(JsonStreamingTokenizer::string())));
    }
}
