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

use App\Parser\FullParser\AbstractParser;
use App\Parser\FullParser\FullParser;
use App\Parser\JsonToken\ArrayEndToken;
use App\Parser\JsonToken\ArrayStartToken;
use App\Parser\JsonToken\KeyToken;
use App\Parser\JsonToken\ObjectEndToken;
use App\Parser\JsonToken\ObjectStartToken;
use App\Parser\JsonToken\ValueToken;

class JsonValueAccumulatorParser extends AbstractParser
{
    public function label(): string
    {
        return 'JSON value';
    }

    public function __invoke(StreamInterface $stream)
    {
        return $stream->consume(self::value());
    }

    public static function value(): ParserInterface
    {
        static $parser = null;

        return $parser
            ?? $parser = FullParser::choice(
                self::object(),
                self::array(),
                self::primitive()
            );
    }

    public static function array(): ParserInterface
    {
        static $parser = null;

        return $parser
            ?? $parser = new class() extends AbstractParser {
                private ParserInterface $arrayStart;
                private ParserInterface $arrayEnd;

                public function __construct()
                {
                    $this->arrayStart = JsonStreamingParser::token(ArrayStartToken::class);
                    $this->arrayEnd   = JsonStreamingParser::token(ArrayEndToken::class);
                }

                public function label(): string
                {
                    return 'JSON array';
                }

                public function __invoke(StreamInterface $stream)
                {
                    $stream->consume($this->arrayStart);
                    $array = $stream->consume(JsonValueAccumulatorParser::value()->repeated()->optional());
                    $stream->consume($this->arrayEnd);

                    return $array;
                }
            };
    }

    public static function object(): ParserInterface
    {
        static $parser = null;

        return $parser
            ?? $parser = new class() extends AbstractParser {
                private ParserInterface $objectStart;
                private ParserInterface $objectEnd;
                private ParserInterface $key;

                public function __construct()
                {
                    $this->objectStart = JsonStreamingParser::token(ObjectStartToken::class);
                    $this->objectEnd   = JsonStreamingParser::token(ObjectEndToken::class);
                    $this->key         = JsonStreamingParser::token(KeyToken::class)->map(fn (KeyToken $token) => $token->key)->optional();
                }

                public function label(): string
                {
                    return 'JSON object';
                }

                public function __invoke(StreamInterface $stream)
                {
                    $stream->consume($this->objectStart);

                    $object = [];
                    while (FullParser::isValid($key = $stream->consume($this->key))) {
                        $object[$key] = $stream->consume(JsonValueAccumulatorParser::value());
                    }

                    $stream->consume($this->objectEnd);

                    return $object;
                }
            };
    }

    public static function primitive(): ParserInterface
    {
        static $parser = null;

        return $parser
            ?? $parser = JsonStreamingParser::token(ValueToken::class)->map(fn (ValueToken $token) => $token->value);
    }
}
