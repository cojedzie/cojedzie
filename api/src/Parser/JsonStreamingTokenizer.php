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
use App\Parser\JsonToken\ValueToken;
use App\Parser\StreamingParser\AbstractStreamingParser;
use App\Parser\StreamingParser\StreamingParser;

class JsonStreamingTokenizer extends AbstractStreamingParser
{
    public function label(): string
    {
        return 'JSON';
    }

    public function __invoke(StreamInterface $stream)
    {
        $input = $stream->peek(1);

        yield from match ($input) {
            '{'     => $stream->consume(self::object()),
            '['     => $stream->consume(self::array()),
            default => throw UnexpectedTokenException::createWithExpected($input, '{ or [', $stream->tell()),
        };
    }

    public static function object(): StreamingParserInterface
    {
        static $parser = null;

        return $parser
            ?? $parser = new class() extends AbstractStreamingParser {
                private ParserInterface $objectEnd;
                private ParserInterface $objectStart;

                public function __construct()
                {
                    $this->objectStart = FullParser::string('{')->map(fn () => new ObjectStartToken());
                    $this->objectEnd   = FullParser::string('}')->map(fn () => new ObjectEndToken());
                }

                public function label(): string
                {
                    return 'JSON object';
                }

                public function __invoke(StreamInterface $stream)
                {
                    yield $stream->consume($this->objectStart);
                    $stream->skip(FullParser::whitespace());
                    yield from $stream->consume(JsonStreamingTokenizer::members());
                    $stream->skip(FullParser::whitespace());
                    yield $stream->consume($this->objectEnd);
                }
            };
    }

    public static function array(): StreamingParserInterface
    {
        static $parser = null;

        return $parser
            ?? $parser = new class() extends AbstractStreamingParser {
                private ParserInterface $arrayStart;
                private ParserInterface $arrayEnd;

                public function __construct()
                {
                    $this->arrayStart = FullParser::string('[')->map(fn () => new ArrayStartToken());
                    $this->arrayEnd   = FullParser::string(']')->map(fn () => new ArrayEndToken());
                }

                public function label(): string
                {
                    return 'JSON array';
                }

                public function __invoke(StreamInterface $stream)
                {
                    yield $stream->consume($this->arrayStart);
                    $stream->skip(FullParser::whitespace());
                    yield from $stream->consume(self::values());
                    $stream->skip(FullParser::whitespace());
                    yield $stream->consume($this->arrayEnd);
                }

                public static function values()
                {
                    static $parser = null;

                    return $parser
                        ?? $parser = StreamingParser::separatedBy(
                            StreamingParser::between(JsonStreamingTokenizer::value(), FullParser::whitespace()),
                            JsonStreamingTokenizer::comma(),
                        )->optional();
                }
            };
    }

    public static function string(): ParserInterface
    {
        static $parser = null;

        return $parser
            ?? new class() extends AbstractParser {
                private ParserInterface $quote;

                public function __construct()
                {
                    $this->quote = FullParser::string('"');
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

    public static function members(): StreamingParserInterface
    {
        static $parser = null;

        return $parser
            ?? $parser = StreamingParser::separatedBy(
                self::member(),
                self::comma()
            )->optional();
    }

    public static function value(): StreamingParserInterface
    {
        static $parser = null;

        return $parser
            ?? $parser = new class() extends AbstractStreamingParser {
                private ParserInterface $array;
                private ParserInterface $object;
                private ParserInterface $string;
                private ParserInterface $boolean;
                private ParserInterface $null;
                private ParserInterface $number;

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

    public static function null(): ParserInterface
    {
        static $parser = null;

        return $parser
            ?? $parser = FullParser::string('null')->map(fn () => null);
    }

    public static function boolean(): ParserInterface
    {
        static $parser = null;

        return $parser
            ?? $parser = FullParser::choice(
                FullParser::string('true')->map(fn ()  => true),
                FullParser::string('false')->map(fn () => false),
            );
    }

    public static function number(): ParserInterface
    {
        static $parser = null;

        // this parser is not spec compliant at all - but it's fast and working :)
        return $parser
            ?? $parser = FullParser::regex('[0-9\-.]')->repeated()->map(fn ($parts) => floatval(implode('', $parts)));
    }

    public static function member(): StreamingParserInterface
    {
        static $parser = null;

        return $parser
            ?? new class() extends AbstractStreamingParser {
                private ParserInterface $colon;
                private ParserInterface $key;

                public function __construct()
                {
                    $this->colon = FullParser::between(FullParser::string(':'), FullParser::whitespace());
                    $this->key   = JsonStreamingTokenizer::string()->map(fn ($value) => new KeyToken($value));
                }

                public function label(): string
                {
                    return 'object member';
                }

                public function __invoke(StreamInterface $stream)
                {
                    $stream->skip(FullParser::whitespace());
                    yield $stream->consume($this->key);
                    $stream->skip($this->colon);
                    yield from $stream->consume(JsonStreamingTokenizer::value());
                    $stream->skip(FullParser::whitespace());
                }
            };
    }

    public static function comma(): ParserInterface
    {
        static $parser = null;

        return $parser
            ?? $parser = FullParser::string(',');
    }
}
