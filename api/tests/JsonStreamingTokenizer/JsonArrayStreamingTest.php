<?php

namespace App\Tests\JsonStreamingTokenizer;

use App\Parser\Json\JsonStreamingTokenizer;
use App\Parser\Json\JsonToken\ArrayEndToken;
use App\Parser\Json\JsonToken\ArrayStartToken;
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
            $parser($stream),
            fn ($token) => $this->assertInstanceOf(ArrayStartToken::class, $token),
            fn ($token) => $this->assertInstanceOf(ArrayEndToken::class, $token),
        );
    }

    public function testArrayWithString(): void
    {
        $parser = new JsonStreamingTokenizer();
        $stream = new StringStream('["foo"]');

        $this->assertStream(
            $parser($stream),
            fn ($token) => $this->assertInstanceOf(ArrayStartToken::class, $token),
            fn ($token) => $this->assertTokenValue("foo", $token),
            fn ($token) => $this->assertInstanceOf(ArrayEndToken::class, $token),
        );
    }

    public function testArrayWithMultipleStringValues(): void
    {
        $parser = new JsonStreamingTokenizer();
        $stream = new StringStream('["foo", "bar"]');

        $this->assertStream(
            $parser($stream),
            fn ($token) => $this->assertInstanceOf(ArrayStartToken::class, $token),
            fn ($token) => $this->assertTokenValue("foo", $token),
            fn ($token) => $this->assertTokenValue("bar", $token),
            fn ($token) => $this->assertInstanceOf(ArrayEndToken::class, $token),
        );
    }
}
