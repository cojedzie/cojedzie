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

use App\Parser\FullConsumer\CallbackConsumer;
use App\Parser\FullConsumer\FullConsumer;
use App\Parser\StreamingConsumer\CallbackStreamingConsumer;
use App\Parser\StreamingConsumer\StreamingConsumer;
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
            $objectStartConsumer = FullConsumer::string('{')->map(fn () => new ObjectStartToken());
            $objectEndConsumer = FullConsumer::string('}')->map(fn () => new ObjectEndToken());

            $consumer = new CallbackStreamingConsumer(
                static function (StreamInterface $stream) use ($objectEndConsumer, $objectStartConsumer) {
                    yield $stream->consume($objectStartConsumer);
                    $stream->skip(FullConsumer::whitespace());
                    yield from $stream->consume(self::members());
                    $stream->skip(FullConsumer::whitespace());
                    yield $stream->consume($objectEndConsumer);
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
            $arrayStartConsumer = FullConsumer::string('[')->map(fn () => new ArrayStartToken());
            $arrayEndConsumer = FullConsumer::string(']')->map(fn () => new ArrayEndToken());

            $consumer = new CallbackStreamingConsumer(
                static function (StreamInterface $stream) use ($arrayStartConsumer, $arrayEndConsumer) {
                    yield $stream->consume($arrayStartConsumer);
                    $stream->skip(FullConsumer::whitespace());
                    yield from $stream->consume(self::arrayValues());
                    $stream->skip(FullConsumer::whitespace());
                    yield $stream->consume($arrayEndConsumer);
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
            $quoteConsumer = FullConsumer::string('"');

            $consumer = new CallbackConsumer(
                static function (StreamInterface $stream) use ($quoteConsumer) {
                    $stream->skip($quoteConsumer);

                    $result = [];
                    while (($input = $stream->read(1)) !== '"') {
                        switch (true) {
                            case $input == '\\':
                                $result[] = match ($character = $stream->read(1)) {
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
                                $result[] = $input;
                                break;
                        }
                    }

                    return implode('', $result);
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
                StreamingConsumer::between(self::value(), FullConsumer::whitespace()),
                self::comma(),
            )->optional();
    }

    public static function members()
    {
        static $consumer = null;

        if ($consumer === null) {
            $consumer = StreamingConsumer::separatedBy(
                self::member(),
                self::comma()
            )->optional();
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

            $consumer = (new CallbackStreamingConsumer(
                static function (StreamInterface $stream) use ($numberConsumer, $nullConsumer, $booleanConsumer, $stringConsumer, $objectConsumer, $arrayConsumer) {
                    $first = $stream->peek(1);

                    match (true) {
                        $first == '['                        => yield from $stream->consume($arrayConsumer),
                        $first == '{'                        => yield from $stream->consume($objectConsumer),
                        $first == '"'                        => yield $stream->consume($stringConsumer),
                        $first == 'f' || $first == 't'       => yield $stream->consume($booleanConsumer),
                        $first == 'n'                        => yield $stream->consume($nullConsumer),
                        ctype_digit($first) || $first == '-' => yield $stream->consume($numberConsumer),
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
            ?? $consumer = FullConsumer::string('null')->map(fn () => null);
    }

    public static function boolean()
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = FullConsumer::choice(
                FullConsumer::string('true')->map(fn ()  => true),
                FullConsumer::string('false')->map(fn () => false),
            );
    }

    public static function number()
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = FullConsumer::regex('[0-9\-.]')->repeated()->map(fn ($parts) => floatval(implode('', $parts)));
    }

    public static function member()
    {
        static $consumer = null;

        if (!$consumer) {
            $colonConsumer = FullConsumer::between(FullConsumer::string(':'), FullConsumer::whitespace());
            $keyConsumer = self::string()->map(fn ($value) => new KeyToken($value));

            $consumer = new CallbackStreamingConsumer(
                static function (StreamInterface $stream) use ($colonConsumer, $keyConsumer) {
                    $stream->skip(FullConsumer::whitespace());
                    yield $stream->consume($keyConsumer);
                    $stream->skip($colonConsumer);
                    yield from $stream->consume(self::value());
                    $stream->skip(FullConsumer::whitespace());
                },
                'object member'
            );
        }

        return $consumer;
    }

    public static function comma()
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = FullConsumer::string(',');
    }
}
