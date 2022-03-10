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

namespace App\Parser\FullConsumer;

use App\Parser\ConsumerInterface;
use App\Parser\StreamInterface;
use JetBrains\PhpStorm\Pure;
use function Kadet\Functional\Predicates\same;

final class FullConsumer
{
    private function __construct()
    {
    }

    #[Pure]
    public static function string(string $string): ConsumerInterface
    {
        return new PredicateConsumer(
            fn ($input) => $input === $string,
            strlen($string),
            $string,
        );
    }

    #[Pure]
    public static function regex(string $pattern, string $flags = ''): ConsumerInterface
    {
        $regex = sprintf('/%s/%s', $pattern, $flags);
        return new PredicateConsumer(
            fn ($char) => preg_match($regex, $char),
            1,
            $pattern,
        );
    }

    #[Pure]
    public static function whitespace(): ConsumerInterface
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = new WhitespaceConsumer();
    }

    #[Pure]
    public static function optional(ConsumerInterface $consumer): OptionalConsumer
    {
        return $consumer instanceof OptionalConsumer ? $consumer : new OptionalConsumer($consumer);
    }

    #[Pure]
    public static function separatedBy(ConsumerInterface $consumer, ConsumerInterface $separator): SeparatedByConsumer
    {
        return new SeparatedByConsumer($consumer, $separator);
    }

    #[Pure]
    public static function between(ConsumerInterface $consumer, ConsumerInterface $left, ConsumerInterface $right = null)
    {
        return new BetweenConsumer($consumer, $left, $right);
    }

    #[Pure]
    public static function choice(ConsumerInterface ...$consumers): ConsumerInterface
    {
        return new AnyConsumer(...$consumers);
    }

    #[Pure]
    public static function sequence(ConsumerInterface ...$consumers): ConsumerInterface
    {
        return new SequenceConsumer(...$consumers);
    }

    #[Pure]
    public static function ignore(ConsumerInterface $consumer): ConsumerInterface
    {
        return $consumer instanceof IgnoredConsumer ? $consumer : new IgnoredConsumer($consumer);
    }

    #[Pure]
    public static function many(ConsumerInterface $consumer): ConsumerInterface
    {
        return new RepeatedConsumer($consumer);
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
