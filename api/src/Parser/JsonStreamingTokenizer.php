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
use App\Parser\FullConsumer\AbstractConsumer;
use App\Parser\FullConsumer\FullConsumer;
use App\Parser\JsonToken\ArrayEndToken;
use App\Parser\JsonToken\ArrayStartToken;
use App\Parser\JsonToken\KeyToken;
use App\Parser\JsonToken\ObjectEndToken;
use App\Parser\JsonToken\ObjectStartToken;
use App\Parser\JsonToken\ValueToken;
use App\Parser\StreamingConsumer\AbstractStreamingConsumer;
use App\Parser\StreamingConsumer\StreamingConsumer;

class JsonStreamingTokenizer
{
    public function parse(StreamInterface $stream)
    {
        $input = $stream->peek(1);

        yield from match ($input) {
            '{'     => $stream->consume(self::object()),
            '['     => $stream->consume(self::array()),
            default => throw UnexpectedTokenException::createWithExpected($input, '{ or [', $stream->tell()),
        };
    }

    public static function object(): StreamingConsumerInterface
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = new class() extends AbstractStreamingConsumer {
                private ConsumerInterface $objectEnd;
                private ConsumerInterface $objectStart;

                public function __construct()
                {
                    $this->objectStart = FullConsumer::string('{')->map(fn () => new ObjectStartToken());
                    $this->objectEnd   = FullConsumer::string('}')->map(fn () => new ObjectEndToken());
                }

                public function label(): string
                {
                    return 'JSON object';
                }

                public function __invoke(StreamInterface $stream)
                {
                    yield $stream->consume($this->objectStart);
                    $stream->skip(FullConsumer::whitespace());
                    yield from $stream->consume(JsonStreamingTokenizer::members());
                    $stream->skip(FullConsumer::whitespace());
                    yield $stream->consume($this->objectEnd);
                }
            };
    }

    public static function array(): StreamingConsumerInterface
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = new class() extends AbstractStreamingConsumer {
                private ConsumerInterface $arrayStart;
                private ConsumerInterface $arrayEnd;

                public function __construct()
                {
                    $this->arrayStart = FullConsumer::string('[')->map(fn () => new ArrayStartToken());
                    $this->arrayEnd   = FullConsumer::string(']')->map(fn () => new ArrayEndToken());
                }

                public function label(): string
                {
                    return 'JSON array';
                }

                public function __invoke(StreamInterface $stream)
                {
                    yield $stream->consume($this->arrayStart);
                    $stream->skip(FullConsumer::whitespace());
                    yield from $stream->consume(self::values());
                    $stream->skip(FullConsumer::whitespace());
                    yield $stream->consume($this->arrayEnd);
                }

                public static function values()
                {
                    static $consumer = null;

                    return $consumer
                        ?? $consumer = StreamingConsumer::separatedBy(
                            StreamingConsumer::between(JsonStreamingTokenizer::value(), FullConsumer::whitespace()),
                            JsonStreamingTokenizer::comma(),
                        )->optional();
                }
            };
    }

    public static function string(): ConsumerInterface
    {
        static $consumer = null;

        return $consumer
            ?? new class() extends AbstractConsumer {
                private ConsumerInterface $quote;

                public function __construct()
                {
                    $this->quote = FullConsumer::string('"');
                }

                public function label(): string
                {
                    return 'JSON string literal';
                }

                public function __invoke(StreamInterface $stream)
                {
                    $stream->skip($this->quote);

                    $result = [];
                    while (($input = $stream->read(1)) !== '"') {
                        $result[] = match ($input) {
                            '\\'     => match ($character = $stream->read(1)) {
                                '\\' => '\\',
                                '/'  => '/',
                                't'  => "\t",
                                'r'  => "\r",
                                'f'  => "\f",
                                'b'  => mb_chr(8),
                                'n'  => "\n",
                                '"'  => '"',
                                'u'  => mb_chr(hexdec($stream->read(4))),
                                // no break
                                default => throw UnexpectedTokenException::create("Undefined escape sequence \\$character", $stream->tell()),
                            },
                            default => $input,
                        };
                    }

                    return implode('', $result);
                }
            };
    }

    public static function members(): StreamingConsumerInterface
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = StreamingConsumer::separatedBy(
                self::member(),
                self::comma()
            )->optional();
    }

    public static function value(): StreamingConsumerInterface
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = new class() extends AbstractStreamingConsumer {
                private ConsumerInterface $array;
                private ConsumerInterface $object;
                private ConsumerInterface $string;
                private ConsumerInterface $boolean;
                private ConsumerInterface $null;
                private ConsumerInterface $number;

                public function __construct()
                {
                    $this->array   = JsonStreamingTokenizer::array();
                    $this->object  = JsonStreamingTokenizer::object();
                    $this->string  = JsonStreamingTokenizer::string()->map(ValueToken::createFromValue(...));
                    $this->boolean = JsonStreamingTokenizer::boolean()->map(ValueToken::createFromValue(...));
                    $this->null    = JsonStreamingTokenizer::null()->map(ValueToken::createFromValue(...));
                    $this->number  = JsonStreamingTokenizer::number()->map(ValueToken::createFromValue(...));
                }

                public function label(): string
                {
                    return 'JSON value';
                }

                public function __invoke(StreamInterface $stream)
                {
                    $first = $stream->peek(1);

                    match (true) {
                        $first == '['                        => yield from $stream->consume($this->array),
                        $first == '{'                        => yield from $stream->consume($this->object),
                        $first == '"'                        => yield $stream->consume($this->string),
                        $first == 'f' || $first == 't'       => yield $stream->consume($this->boolean),
                        $first == 'n'                        => yield $stream->consume($this->null),
                        ctype_digit($first) || $first == '-' => yield $stream->consume($this->number),
                        default                              => throw UnexpectedTokenException::createWithExpected($first, '[, {, ", true, false, null or digit', $stream->tell()),
                    };

                    return true;
                }
            };
    }

    public static function null(): ConsumerInterface
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = FullConsumer::string('null')->map(fn () => null);
    }

    public static function boolean(): ConsumerInterface
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = FullConsumer::choice(
                FullConsumer::string('true')->map(fn ()  => true),
                FullConsumer::string('false')->map(fn () => false),
            );
    }

    public static function number(): ConsumerInterface
    {
        static $consumer = null;

        // this consumer is not spec compliant at all - but it's fast and working :)
        return $consumer
            ?? $consumer = FullConsumer::regex('[0-9\-.]')->repeated()->map(fn ($parts) => floatval(implode('', $parts)));
    }

    public static function member(): StreamingConsumerInterface
    {
        static $consumer = null;

        return $consumer
            ?? new class() extends AbstractStreamingConsumer {
                private ConsumerInterface $colon;
                private ConsumerInterface $key;

                public function __construct()
                {
                    $this->colon = FullConsumer::between(FullConsumer::string(':'), FullConsumer::whitespace());
                    $this->key   = JsonStreamingTokenizer::string()->map(fn ($value) => new KeyToken($value));
                }

                public function label(): string
                {
                    return 'object member';
                }

                public function __invoke(StreamInterface $stream)
                {
                    $stream->skip(FullConsumer::whitespace());
                    yield $stream->consume($this->key);
                    $stream->skip($this->colon);
                    yield from $stream->consume(JsonStreamingTokenizer::value());
                    $stream->skip(FullConsumer::whitespace());
                }
            };
    }

    public static function comma(): ConsumerInterface
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = FullConsumer::string(',');
    }
}
