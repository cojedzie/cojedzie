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

use App\Parser\StreamInterface;
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

    public static function regex(string $pattern, string $flags = ''): ConsumerInterface
    {
        return new PredicateConsumer(
            fn ($char) => preg_match(sprintf('/%s/%s', $pattern, $flags), $char),
            1,
            $pattern,
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

    public static function choice(ConsumerInterface ...$consumers): ConsumerInterface
    {
        return new AnyConsumer(...$consumers);
    }

    public static function sequence(ConsumerInterface ...$consumers): ConsumerInterface
    {
        return new CallbackConsumer(
            function (StreamInterface $stream) use ($consumers) {
                foreach ($consumers as $consumer) {
                    $generator = $stream->consume($consumer);
                    foreach ($generator as $result) {
                        yield $result;
                    }
                }

                return true;
            },
            implode(' then ', array_map(fn (ConsumerInterface $consumer) => $consumer->label(), $consumers)),
        );
    }

    public static function ignore(ConsumerInterface $consumer): ConsumerInterface
    {
        return $consumer instanceof IgnoredConsumer ? $consumer : new IgnoredConsumer($consumer);
    }

    public static function many(ConsumerInterface $consumer): ConsumerInterface
    {
        return new CallbackConsumer(
            static function (StreamInterface $stream) use ($consumer) {
                $successful = false;

                do {
                    $result = $stream->consume(Consumer::optional($consumer));
                    yield from $result;
                    $successful = $successful || Consumer::isValid($result);
                } while (Consumer::isValid($result));

                return $successful;
            },
            sprintf("multiple %s", $consumer->label())
        );
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
