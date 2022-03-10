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

namespace App\Parser\StreamingConsumer;

use App\Parser\ConsumerInterface;
use App\Parser\StreamingConsumerInterface;
use JetBrains\PhpStorm\Pure;

final class StreamingConsumer
{
    private function __construct()
    {
    }

    #[Pure]
    public static function string(string $string): StreamingConsumerInterface
    {
        return new PredicateStreamingConsumer(
            fn ($input) => $input === $string,
            strlen($string),
            $string,
        );
    }

    #[Pure]
    public static function regex(string $pattern, string $flags = ''): StreamingConsumerInterface
    {
        $regex = sprintf('/%s/%s', $pattern, $flags);
        return new PredicateStreamingConsumer(
            fn ($char) => preg_match($regex, $char),
            1,
            $pattern,
        );
    }

    #[Pure]
    public static function whitespace(): StreamingConsumerInterface
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = new WhitespaceStreamingConsumer();
    }

    #[Pure]
    public static function optional(StreamingConsumerInterface $consumer): OptionalStreamingConsumer
    {
        return $consumer instanceof OptionalStreamingConsumer ? $consumer : new OptionalStreamingConsumer($consumer);
    }

    #[Pure]
    public static function separatedBy(StreamingConsumerInterface $consumer, ConsumerInterface $separator): SeparatedByStreamingConsumer
    {
        return new SeparatedByStreamingConsumer($consumer, $separator);
    }

    #[Pure]
    public static function between(StreamingConsumerInterface $consumer, ConsumerInterface $left, ConsumerInterface $right = null)
    {
        return new BetweenStreamingConsumer($consumer, $left, $right);
    }

    #[Pure]
    public static function choice(StreamingConsumerInterface ...$consumers): StreamingConsumerInterface
    {
        return new AnyStreamingConsumer(...$consumers);
    }

    #[Pure]
    public static function sequence(StreamingConsumerInterface ...$consumers): StreamingConsumerInterface
    {
        return new SequenceStreamingConsumer(...$consumers);
    }

    #[Pure]
    public static function ignore(StreamingConsumerInterface $consumer): StreamingConsumerInterface
    {
        return $consumer instanceof IgnoredStreamingConsumer ? $consumer : new IgnoredStreamingConsumer($consumer);
    }

    #[Pure]
    public static function many(StreamingConsumerInterface $consumer): StreamingConsumerInterface
    {
        return new RepeatedConsumer($consumer);
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

    #[Pure]
    public static function streamify(ConsumerInterface $consumer): StreamingConsumerInterface
    {
        return $consumer instanceof StreamingConsumerInterface
            ? $consumer
            : new StreamifiedConsumer($consumer);
    }
}
