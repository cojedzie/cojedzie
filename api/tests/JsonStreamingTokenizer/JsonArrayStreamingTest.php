<?php

namespace App\Tests\JsonStreamingTokenizer;

use App\Parser\JsonStreamingTokenizer;
use App\Parser\JsonToken;
use App\Parser\StringStream;
use App\Tests\Utils\JsonTokenizerAssertions;
use App\Tests\Utils\StreamTestAssertions;
use PHPUnit\Framework\TestCase;

class JsonArrayStreamingTest extends TestCase
{
    use JsonTokenizerAssertions, StreamTestAssertions;

    public function testEmptyArray(): void
    {
        $parser = new JsonStreamingTokenizer();
        $stream = new StringStream('[]');

        $this->assertStream(
            $parser->parse($stream),
            fn ($token) => $this->assertInstanceOf(JsonToken\ArrayStartToken::class, $token),
            fn ($token) => $this->assertInstanceOf(JsonToken\ArrayEndToken::class, $token),
        );
    }

    public function testArrayWithString(): void
    {
        $parser = new JsonStreamingTokenizer();
        $stream = new StringStream('["foo"]');

        $this->assertStream(
            $parser->parse($stream),
            fn ($token) => $this->assertInstanceOf(JsonToken\ArrayStartToken::class, $token),
            fn ($token) => $this->assertTokenValue("foo", $token),
            fn ($token) => $this->assertInstanceOf(JsonToken\ArrayEndToken::class, $token),
        );
    }

    public function testArrayWithMultipleStringValues(): void
    {
        $parser = new JsonStreamingTokenizer();
        $stream = new StringStream('["foo", "bar"]');

        $this->assertStream(
            $parser->parse($stream),
            fn ($token) => $this->assertInstanceOf(JsonToken\ArrayStartToken::class, $token),
            fn ($token) => $this->assertTokenValue("foo", $token),
            fn ($token) => $this->assertTokenValue("bar", $token),
            fn ($token) => $this->assertInstanceOf(JsonToken\ArrayEndToken::class, $token),
        );
    }
}
