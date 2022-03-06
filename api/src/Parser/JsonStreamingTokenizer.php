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

use App\Parser\Consumer\CallbackConsumer;
use App\Parser\Consumer\Consumer;
use App\Parser\Exception\UnexpectedTokenException;
use App\Parser\JsonToken\ArrayEndToken;
use App\Parser\JsonToken\ArrayStartToken;
use App\Parser\JsonToken\KeyToken;
use App\Parser\JsonToken\ObjectEndToken;
use App\Parser\JsonToken\ObjectStartToken;
use App\Parser\JsonToken\ValueToken;
use App\Parser\JsonToken\ValueTokenType;

class JsonStreamingTokenizer
{
    public function parse(StreamInterface $stream)
    {
        $input = $stream->peek(1);

        yield from match ($input) {
            '{'     => $stream->consume(self::object()),
            '['     => $stream->consume(self::array()),
            default => throw UnexpectedTokenException::create($input, '{ or ['),
        };
    }

    public static function object()
    {
        static $consumer;

        return $consumer
            ?? $consumer = new CallbackConsumer(
                static function (StreamInterface $stream) {
                    yield from $stream->consume(
                        Consumer::string('{')->map(fn () => new ObjectStartToken())
                    );

                    $stream->skip(Consumer::whitespace());

                    $members = $stream->consume(self::members());
                    if (Consumer::isValid($members)) {
                        foreach ($members as $member) {
                            yield $member;
                        }
                    }

                    $stream->skip(Consumer::whitespace());

                    yield from $stream->consume(
                        Consumer::string('}')->map(fn () => new ObjectEndToken())
                    );
                },
                'JSON object'
            );
    }

    public static function array()
    {
        static $consumer;

        return $consumer
            ?? $consumer = new CallbackConsumer(
                static function (StreamInterface $stream) {
                    yield from $stream->consume(
                        Consumer::string('[')->map(fn () => new ArrayStartToken())
                    );

                    $stream->skip(Consumer::whitespace());

                    $values = $stream->consume(self::arrayValues());
                    if (Consumer::isValid($values)) {
                        foreach ($values as $value) {
                            yield $value;
                        }
                    }

                    $stream->skip(Consumer::whitespace());

                    yield from $stream->consume(
                        Consumer::string(']')->map(fn () => new ArrayEndToken())
                    );
                },
                'JSON array'
            );
    }

    public static function string()
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = new CallbackConsumer(
                static function (StreamInterface $stream) {
                    $stream->skip(Consumer::string('"'));

                    $result = "";
                    while ($input = $stream->peek(1)) {
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
                            case $input == '"':
                                break 2; // string end
                            default:
                                $result .= $stream->read(1);
                                break;
                        }
                    }

                    $stream->skip(Consumer::string('"'));

                    yield $result;

                    return true;
                },
                'JSON string literal'
            );
    }

    public static function arrayValues()
    {
        static $consumer = null;

        if ($consumer === null) {
            $consumer = self::value();
            $consumer = Consumer::separatedBy($consumer, self::comma());
            $consumer = Consumer::optional($consumer);
        }

        return $consumer;
    }

    public static function members()
    {
        static $consumer = null;

        if ($consumer === null) {
            $consumer = self::member();
            $consumer = Consumer::separatedBy($consumer, self::comma());
            $consumer = Consumer::optional($consumer);
        }

        return $consumer;
    }

    public static function value()
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = self::string()->map(ValueToken::createFromValue(...));
    }

    public static function member()
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = new CallbackConsumer(
                static function (StreamInterface $stream) {
                    yield from $stream->consume(self::string()->map(fn ($value) => new KeyToken($value)));
                    $stream->skip(Consumer::between(Consumer::string(':'), Consumer::whitespace()));
                    yield from $stream->consume(self::string()->map(fn ($value) => new ValueToken(ValueTokenType::String, $value)));
                },
                'object member'
            );
    }

    public static function comma()
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = Consumer::between(
                Consumer::string(','),
                Consumer::whitespace()
            );
    }
}
