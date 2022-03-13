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

use App\Parser\Exception\UnexpectedTokenException;
use App\Parser\FullParser\AbstractParser;
use App\Parser\FullParser\FullParser;
use App\Parser\JsonToken\ArrayEndToken;
use App\Parser\JsonToken\ArrayStartToken;
use App\Parser\JsonToken\KeyToken;
use App\Parser\JsonToken\ObjectEndToken;
use App\Parser\JsonToken\ObjectStartToken;
use App\Parser\StreamingParser\AbstractStreamingParser;
use App\Parser\StreamingParser\StreamingParser;

class JsonStreamingParser extends AbstractStreamingParser
{
    public function __construct(
        private string $path
    ) {
    }

    public function label(): string
    {
        return 'JSON streaming values';
    }

    public function __invoke(StreamInterface $stream): \Generator
    {
        yield from $stream->consume($this->value());
    }

    public function value(string $path = '')
    {
        if (fnmatch($this->path, $path)) {
            return JsonValueAccumulatorParser::value()->streamify();
        }

        return new class($path, $this) extends AbstractStreamingParser {
            public function __construct(
                private string $path,
                private JsonStreamingParser $json,
            ) {
            }

            public function label(): string
            {
                return "JSON object";
            }

            public function __invoke(StreamInterface $stream)
            {
                $token = $stream->peek()->first();

                match (true) {
                    $token instanceof ObjectStartToken => yield from $stream->consume($this->json->object($this->path)),
                    $token instanceof ArrayStartToken  => yield from $stream->consume($this->json->array($this->path)),
                    default                            => null,
                };

                return null;
            }
        };
    }

    public function object(string $path = "")
    {
        return new class($path, $this) extends AbstractParser {
            private ParserInterface $objectStart;
            private ParserInterface $objectEnd;
            private ParserInterface $key;

            public function __construct(
                private string $path,
                private JsonStreamingParser $json,
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

    public function array(string $path = "")
    {
        return new class($path, $this) extends AbstractParser {
            private ParserInterface $arrayStart;
            private ParserInterface $arrayEnd;

            public function __construct(
                private string $path,
                private JsonStreamingParser $json,
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
                while (StreamingParser::isValid($result = $stream->consume($this->json->value("{$this->path}.{$index}")))) {
                    foreach ($result as $value) {
                        yield $value;
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
                    private string $class
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
}
