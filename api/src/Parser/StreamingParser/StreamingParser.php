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

namespace App\Parser\StreamingParser;

use App\Parser\ParserInterface;
use App\Parser\StreamingParserInterface;
use JetBrains\PhpStorm\Pure;

final class StreamingParser
{
    private function __construct()
    {
    }

    #[Pure]
    public static function string(string $string): StreamingParserInterface
    {
        return new PredicateStreamingParser(
            fn ($input) => $input === $string,
            strlen($string),
            $string,
        );
    }

    #[Pure]
    public static function regex(string $pattern, string $flags = ''): StreamingParserInterface
    {
        $regex = sprintf('/%s/%s', $pattern, $flags);
        return new PredicateStreamingParser(
            fn ($char) => preg_match($regex, $char),
            1,
            $pattern,
        );
    }

    #[Pure]
    public static function whitespace(): StreamingParserInterface
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = new WhitespaceStreamingParser();
    }

    #[Pure]
    public static function optional(StreamingParserInterface $consumer): OptionalStreamingParser
    {
        return $consumer instanceof OptionalStreamingParser ? $consumer : new OptionalStreamingParser($consumer);
    }

    #[Pure]
    public static function separatedBy(StreamingParserInterface $consumer, ParserInterface $separator): SeparatedByStreamingParser
    {
        return new SeparatedByStreamingParser($consumer, $separator);
    }

    #[Pure]
    public static function between(StreamingParserInterface $consumer, ParserInterface $left, ParserInterface $right = null)
    {
        return new BetweenStreamingParser($consumer, $left, $right);
    }

    #[Pure]
    public static function choice(StreamingParserInterface ...$consumers): StreamingParserInterface
    {
        return new AnyStreamingParser(...$consumers);
    }

    #[Pure]
    public static function sequence(StreamingParserInterface ...$consumers): StreamingParserInterface
    {
        return new SequenceStreamingParser(...$consumers);
    }

    #[Pure]
    public static function ignore(StreamingParserInterface $consumer): StreamingParserInterface
    {
        return $consumer instanceof IgnoredStreamingParser ? $consumer : new IgnoredStreamingParser($consumer);
    }

    #[Pure]
    public static function many(StreamingParserInterface $consumer): StreamingParserInterface
    {
        return new RepeatedParser($consumer);
    }

    public static function isEmpty(\Generator $result): bool
    {
        return !self::isValid($result);
    }

    public static function result(\Generator $result)
    {
        return $result->current();
    }

    public static function isValid(\Generator $result): bool
    {
        return $result->valid() || $result->getReturn();
    }

    #[Pure]
    public static function streamify(ParserInterface $consumer): StreamingParserInterface
    {
        return $consumer instanceof StreamingParserInterface
            ? $consumer
            : new StreamifiedParser($consumer);
    }
}
