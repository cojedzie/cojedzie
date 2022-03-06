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

use App\Parser\StreamingConsumer\CallbackConsumer;
use App\Parser\StreamingConsumer\StreamingConsumer;
use App\Parser\StreamingConsumer\ReducedConsumer;
use App\Parser\Exception\UnexpectedTokenException;
use App\Parser\JsonToken\ArrayEndToken;
use App\Parser\JsonToken\ArrayStartToken;
use App\Parser\JsonToken\KeyToken;
use App\Parser\JsonToken\ObjectEndToken;
use App\Parser\JsonToken\ObjectStartToken;
use App\Parser\JsonToken\ValueToken;

class JsonStreamingTokenizer
{
    public function parse(StreamInterface $stream)
    {
        $input = $stream->peek(1);

        yield from match ($input) {
            '{'     => $stream->consume(self::object()),
            '['     => $stream->consume(self::array()),
            default => throw UnexpectedTokenException::create($input, '{ or [', $stream->tell()),
        };
    }

    public static function object()
    {
        static $consumer;

        if (!$consumer) {
            $objectStartConsumer = StreamingConsumer::string('{')->map(fn () => new ObjectStartToken());
            $objectEndConsumer = StreamingConsumer::string('}')->map(fn () => new ObjectEndToken());

            $consumer = new CallbackConsumer(
                static function (StreamInterface $stream) use ($objectEndConsumer, $objectStartConsumer) {
                    yield from $stream->consume($objectStartConsumer);

                    $stream->skip(StreamingConsumer::whitespace());

                    $members = $stream->consume(self::members());
                    if (StreamingConsumer::isValid($members)) {
                        foreach ($members as $member) {
                            yield $member;
                        }
                    }

                    $stream->skip(StreamingConsumer::whitespace());

                    yield from $stream->consume($objectEndConsumer);
                },
                'JSON object'
            );
        }

        return $consumer;
    }

    public static function array()
    {
        static $consumer;

        if (!$consumer) {
            $arrayStartConsumer = StreamingConsumer::string('[')->map(fn () => new ArrayStartToken());
            $arrayEndConsumer = StreamingConsumer::string(']')->map(fn () => new ArrayEndToken());

            $consumer = new CallbackConsumer(
                static function (StreamInterface $stream) use ($arrayStartConsumer, $arrayEndConsumer) {
                    yield from $stream->consume($arrayStartConsumer);

                    $stream->skip(StreamingConsumer::whitespace());

                    $values = $stream->consume(self::arrayValues());
                    if (StreamingConsumer::isValid($values)) {
                        foreach ($values as $value) {
                            yield $value;
                        }
                    }

                    $stream->skip(StreamingConsumer::whitespace());

                    yield from $stream->consume($arrayEndConsumer);
                },
                'JSON array'
            );
        }

        return $consumer;
    }

    public static function string()
    {
        static $consumer = null;

        if (!$consumer) {
            $quoteConsumer = StreamingConsumer::string('"');

            $consumer = new CallbackConsumer(
                static function (StreamInterface $stream) use ($quoteConsumer) {
                    $stream->skip($quoteConsumer);

                    $result = "";
                    while (($input = $stream->peek(1)) !== '"') {
                        switch (true) {
                            case $input == '\\':
                                // consume backslash
                                $stream->read(1);

                                $result .= match ($character = $stream->read(1)) {
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
                                    default => throw new UnexpectedTokenException("Undefined escape sequence \\$character."),
                                };

                                break;
                            default:
                                $result .= $stream->read(1);
                                break;
                        }
                    }

                    $stream->skip($quoteConsumer);

                    yield $result;

                    return true;
                },
                'JSON string literal'
            );
        }

        return $consumer;
    }

    public static function arrayValues()
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = StreamingConsumer::separatedBy(
                StreamingConsumer::between(self::value(), StreamingConsumer::whitespace()),
                self::comma(),
            )->optional();
    }

    public static function members()
    {
        static $consumer = null;

        if ($consumer === null) {
            $consumer = self::member();
            $consumer = StreamingConsumer::separatedBy($consumer, self::comma())->optional();
        }

        return $consumer;
    }

    public static function value()
    {
        static $consumer = null;

        if (!$consumer) {
            $arrayConsumer = self::array();
            $objectConsumer = self::object();
            $stringConsumer = self::string()->map(ValueToken::createFromValue(...));
            $booleanConsumer = self::boolean()->map(ValueToken::createFromValue(...));
            $nullConsumer = self::null()->map(ValueToken::createFromValue(...));
            $numberConsumer = self::number()->map(ValueToken::createFromValue(...));

            $consumer = (new CallbackConsumer(
                function (StreamInterface $stream) use ($numberConsumer, $nullConsumer, $booleanConsumer, $stringConsumer, $objectConsumer, $arrayConsumer) {
                    $first = $stream->peek(1);

                    yield from match (true) {
                        $first == '['                        => $stream->consume($arrayConsumer),
                        $first == '{'                        => $stream->consume($objectConsumer),
                        $first == '"'                        => $stream->consume($stringConsumer),
                        $first == 'f' || $first == 't'       => $stream->consume($booleanConsumer),
                        $first == 'n'                        => $stream->consume($nullConsumer),
                        ctype_digit($first) || $first == '-' => $stream->consume($numberConsumer),
                        default                              => throw UnexpectedTokenException::create($first, '[, {, ", true, false, null or digit', $stream->tell()),
                    };

                    return true;
                },
                'JSON value'
            ));
        }

        return $consumer;
    }

    public static function null()
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = StreamingConsumer::string('null')->map(fn () => null);
    }

    public static function boolean()
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = StreamingConsumer::choice(
                StreamingConsumer::string('true')->map(fn ()  => true),
                StreamingConsumer::string('false')->map(fn () => false),
            );
    }

    public static function number()
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = StreamingConsumer::sequence(
                StreamingConsumer::optional(StreamingConsumer::string('-')),
                StreamingConsumer::choice(
                    StreamingConsumer::string('0'),
                    StreamingConsumer::sequence(
                        StreamingConsumer::regex('[1-9]'),
                        StreamingConsumer::regex('[0-9]')->repeated()
                    )
                ),
                StreamingConsumer::optional(
                    StreamingConsumer::sequence(
                        StreamingConsumer::string('.'),
                        StreamingConsumer::regex('[0-9]')->repeated()
                    )
                ),
            )->reduce(ReducedConsumer::join())->map(floatval(...));
    }

    public static function member()
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = new CallbackConsumer(
                static function (StreamInterface $stream) {
                    $stream->skip(StreamingConsumer::whitespace());
                    yield from $stream->consume(self::string()->map(fn ($value) => new KeyToken($value)));
                    $stream->skip(StreamingConsumer::between(StreamingConsumer::string(':'), StreamingConsumer::whitespace()));
                    yield from $stream->consume(self::value());
                    $stream->skip(StreamingConsumer::whitespace());
                },
                'object member'
            );
    }

    public static function comma()
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = StreamingConsumer::string(',');
    }
}
