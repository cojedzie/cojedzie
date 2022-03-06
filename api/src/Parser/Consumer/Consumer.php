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

namespace App\Parser\Consumer;

use function Kadet\Functional\Predicates\same;

final class Consumer
{
    private function __construct()
    {
    }

    public static function string(string $string): ConsumerInterface
    {
        return new PredicateConsumer(
            same($string),
            strlen($string),
            $string,
        );
    }

    public static function whitespace(): ConsumerInterface
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = new WhitespaceConsumer();
    }

    public static function optional(ConsumerInterface $consumer): OptionalConsumer
    {
        return $consumer instanceof OptionalConsumer ? $consumer : new OptionalConsumer($consumer);
    }

    public static function separatedBy(ConsumerInterface $consumer, ConsumerInterface $separator): SeparatedByConsumer
    {
        return new SeparatedByConsumer($consumer, $separator);
    }

    public static function between(ConsumerInterface $consumer, ConsumerInterface $left, ConsumerInterface $right = null)
    {
        return new BetweenConsumer($consumer, $left, $right);
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
        $result->current();
        return $result->valid() || $result->getReturn();
    }
}
