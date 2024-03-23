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

namespace App\Parser\Json;

use App\Parser\Exception\FullyParsedException;
use App\Parser\Exception\UnexpectedTokenException;
use App\Parser\FullParser\AbstractParser;
use App\Parser\FullParser\FullParser;
use App\Parser\Json\JsonToken\ArrayEndToken;
use App\Parser\Json\JsonToken\ArrayStartToken;
use App\Parser\Json\JsonToken\KeyToken;
use App\Parser\Json\JsonToken\ObjectEndToken;
use App\Parser\Json\JsonToken\ObjectStartToken;
use App\Parser\Json\JsonToken\ValueToken;
use App\Parser\ParserInterface;
use App\Parser\StreamingParser\AbstractStreamingParser;
use App\Parser\StreamInterface;
use function get_debug_type;

class JsonStreamingParser extends AbstractStreamingParser
{
    public function __construct(
        private readonly BranchPathDecider $decider
    ) {
    }

    public function label(): string
    {
        return 'JSON streaming values';
    }

    public function __invoke(StreamInterface $stream): \Generator
    {
        try {
            yield from $stream->consume($this->value());
        } catch (FullyParsedException) {
            // ignore
        }
    }

    public function value(string $path = '')
    {
        return match ($this->decider->decide($path)) {
            PathDecision::Consume  => JsonValueAccumulatorParser::value()->streamify(),
            PathDecision::Continue => new class($path, $this) extends AbstractStreamingParser {
                public function __construct(
                    private readonly string $path,
                    private readonly JsonStreamingParser $json,
                ) {
                }

                public function label(): string
                {
                    return "JSON object";
                }

                public function __invoke(StreamInterface $stream)
                {
                    $token = $stream->peek()->first();

                    switch (true) {
                        case $token instanceof ObjectStartToken:
                            yield from $stream->consume($this->json->object($this->path));
                            return true;
                        case $token instanceof ArrayStartToken:
                            yield from $stream->consume($this->json->array($this->path));
                            return true;
                        case $token instanceof ValueToken:
                            $stream->read();
                            return true;
                        default:
                            return false;
                    }
                }
            },
            PathDecision::Stop => throw new FullyParsedException(),
        };
    }

    public function object(string $path = ""): ParserInterface
    {
        return new class($path, $this) extends AbstractParser {
            private readonly ParserInterface $objectStart;
            private readonly ParserInterface $objectEnd;
            private readonly ParserInterface $key;

            public function __construct(
                private readonly string $path,
                private readonly JsonStreamingParser $json,
            ) {
                $this->objectStart = JsonStreamingParser::token(ObjectStartToken::class);
                $this->objectEnd   = JsonStreamingParser::token(ObjectEndToken::class);
                $this->key         = JsonStreamingParser::token(KeyToken::class)->map(fn (KeyToken $token) => $token->key)->optional();
            }

            public function label(): string
            {
                return "JSON object";
            }

            public function __invoke(StreamInterface $stream)
            {
                $stream->consume($this->objectStart);

                while (FullParser::isValid($key = $stream->consume($this->key))) {
                    yield from $stream->consume($this->json->value("{$this->path}.{$key}"));
                }

                $stream->consume($this->objectEnd);
            }
        };
    }

    public function array(string $path = ""): ParserInterface
    {
        return new class($path, $this) extends AbstractParser {
            private readonly ParserInterface $arrayStart;
            private readonly ParserInterface $arrayEnd;

            public function __construct(
                private readonly string $path,
                private readonly JsonStreamingParser $json,
            ) {
                $this->arrayStart = JsonStreamingParser::token(ArrayStartToken::class);
                $this->arrayEnd   = JsonStreamingParser::token(ArrayEndToken::class);
            }

            public function label(): string
            {
                return "JSON array";
            }

            public function __invoke(StreamInterface $stream)
            {
                $stream->consume($this->arrayStart);

                $index = 0;
                while ($result = $stream->consume($this->json->value("{$this->path}.{$index}"))) {
                    foreach ($result as $value) {
                        yield $value;
                    }

                    if (!$result->getReturn()) {
                        break;
                    }

                    $index++;
                }

                $stream->consume($this->arrayEnd);
            }
        };
    }

    public static function token(string $class): ParserInterface
    {
        static $parsers = [];

        return $parsers[$class]
            ?? $parsers[$class] = new class($class) extends AbstractParser {
                public function __construct(
                    private readonly string $class
                ) {
                }

                public function label(): string
                {
                    return "token " . $this->class;
                }

                public function __invoke(StreamInterface $stream)
                {
                    $token = $stream->peek()->first();

                    if (!$token instanceof $this->class) {
                        throw UnexpectedTokenException::createWithExpected(get_debug_type($token), $this->class, $stream->tell());
                    }

                    return $stream->read()->first();
                }
            };
    }

    public static function path(string $pattern)
    {
        return function ($path) use ($pattern) {
            static $state = 'waiting';
        };
    }
}
