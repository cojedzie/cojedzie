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

namespace App\Parser\FullParser;

use App\Parser\ParserInterface;
use JetBrains\PhpStorm\Pure;

final class FullParser
{
    private function __construct()
    {
    }

    #[Pure]
    public static function string(string $string): ParserInterface
    {
        return new PredicateParser(
            fn ($input) => $input === $string,
            strlen($string),
            $string,
        );
    }

    #[Pure]
    public static function regex(string $pattern, string $flags = ''): ParserInterface
    {
        $regex = sprintf('/%s/%s', $pattern, $flags);
        return new PredicateParser(
            fn ($char) => preg_match($regex, $char),
            1,
            $pattern,
        );
    }

    #[Pure]
    public static function whitespace(): ParserInterface
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = new WhitespaceParser();
    }

    #[Pure]
    public static function optional(ParserInterface $consumer): OptionalParser
    {
        return $consumer instanceof OptionalParser ? $consumer : new OptionalParser($consumer);
    }

    #[Pure]
    public static function separatedBy(ParserInterface $consumer, ParserInterface $separator): SeparatedByParser
    {
        return new SeparatedByParser($consumer, $separator);
    }

    #[Pure]
    public static function between(ParserInterface $consumer, ParserInterface $left, ParserInterface $right = null)
    {
        return new BetweenParser($consumer, $left, $right);
    }

    #[Pure]
    public static function choice(ParserInterface ...$consumers): ParserInterface
    {
        return new AnyParser(...$consumers);
    }

    #[Pure]
    public static function sequence(ParserInterface ...$consumers): ParserInterface
    {
        return new SequenceParser(...$consumers);
    }

    #[Pure]
    public static function ignore(ParserInterface $consumer): ParserInterface
    {
        return $consumer instanceof IgnoredParser ? $consumer : new IgnoredParser($consumer);
    }

    #[Pure]
    public static function many(ParserInterface $consumer): ParserInterface
    {
        return new RepeatedParser($consumer);
    }

    #[Pure]
    public static function isEmpty($result): bool
    {
        return !self::isValid($result);
    }

    #[Pure]
    public static function result($result)
    {
        return $result;
    }

    #[Pure]
    public static function isValid($result): bool
    {
        return $result !== null;
    }
}
