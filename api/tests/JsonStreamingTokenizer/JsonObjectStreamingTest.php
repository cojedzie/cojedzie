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

use App\Parser\JsonStreamingTokenizer;
use App\Parser\JsonToken;
use App\Parser\StringStream;
use App\Tests\Utils\JsonTokenizerAssertions;
use App\Tests\Utils\StreamTestAssertions;
use PHPUnit\Framework\TestCase;

class JsonObjectStreamingTest extends TestCase
{
    use JsonTokenizerAssertions, StreamTestAssertions;

    public function testEmptyObject(): void
    {
        $parser = new JsonStreamingTokenizer();
        $stream = new StringStream('{}');

        $this->assertStream(
            $parser($stream),
            fn ($token) => $this->assertInstanceOf(JsonToken\ObjectStartToken::class, $token),
            fn ($token) => $this->assertInstanceOf(JsonToken\ObjectEndToken::class, $token),
        );
    }

    public function testSimpleObjectWithOneKey(): void
    {
        $parser = new JsonStreamingTokenizer();
        $stream = new StringStream('{"foo": "bar"}');

        $this->assertStream(
            $parser($stream),
            fn ($token) => $this->assertInstanceOf(JsonToken\ObjectStartToken::class, $token),
            fn ($token) => $this->assertTokenKey("foo", $token),
            fn ($token) => $this->assertTokenValue("bar", $token),
            fn ($token) => $this->assertInstanceOf(JsonToken\ObjectEndToken::class, $token),
        );
    }

    public function testSimpleObjectWithMultipleValues(): void
    {
        $parser = new JsonStreamingTokenizer();
        $stream = new StringStream('{"foo": "bar", "baz": "foo"}');

        $this->assertStream(
            $parser($stream),
            fn ($token) => $this->assertInstanceOf(JsonToken\ObjectStartToken::class, $token),
            fn ($token) => $this->assertTokenKey("foo", $token),
            fn ($token) => $this->assertTokenValue("bar", $token),
            fn ($token) => $this->assertTokenKey("baz", $token),
            fn ($token) => $this->assertTokenValue("foo", $token),
            fn ($token) => $this->assertInstanceOf(JsonToken\ObjectEndToken::class, $token),
        );
    }
}
