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
use App\Parser\StreamInterface;
use function Kadet\Functional\Predicates\same;

final class StreamingConsumer
{
    private function __construct()
    {
    }

    public static function string(string $string): StreamingConsumerInterface
    {
        return new PredicateStreamingConsumer(
            same($string),
            strlen($string),
            $string,
        );
    }

    public static function regex(string $pattern, string $flags = ''): StreamingConsumerInterface
    {
        return new PredicateStreamingConsumer(
            fn ($char) => preg_match(sprintf('/%s/%s', $pattern, $flags), $char),
            1,
            $pattern,
        );
    }

    public static function whitespace(): StreamingConsumerInterface
    {
        static $consumer = null;

        return $consumer
            ?? $consumer = new WhitespaceStreamingConsumer();
    }

    public static function optional(StreamingConsumerInterface $consumer): OptionalStreamingConsumer
    {
        return $consumer instanceof OptionalStreamingConsumer ? $consumer : new OptionalStreamingConsumer($consumer);
    }

    public static function separatedBy(StreamingConsumerInterface $consumer, StreamingConsumerInterface $separator): SeparatedByStreamingConsumer
    {
        return new SeparatedByStreamingConsumer($consumer, $separator);
    }

    public static function between(StreamingConsumerInterface $consumer, StreamingConsumerInterface $left, StreamingConsumerInterface $right = null)
    {
        return new BetweenStreamingConsumer($consumer, $left, $right);
    }

    public static function choice(StreamingConsumerInterface ...$consumers): StreamingConsumerInterface
    {
        return new AnyStreamingConsumer(...$consumers);
    }

    public static function sequence(StreamingConsumerInterface ...$consumers): StreamingConsumerInterface
    {
        return new CallbackStreamingConsumer(
            function (StreamInterface $stream) use ($consumers) {
                foreach ($consumers as $consumer) {
                    $generator = $stream->consume($consumer);
                    foreach ($generator as $result) {
                        yield $result;
                    }
                }

                return true;
            },
            implode(' then ', array_map(fn (StreamingConsumerInterface $consumer) => $consumer->label(), $consumers)),
        );
    }

    public static function ignore(StreamingConsumerInterface $consumer): StreamingConsumerInterface
    {
        return $consumer instanceof IgnoredStreamingConsumer ? $consumer : new IgnoredStreamingConsumer($consumer);
    }

    public static function many(StreamingConsumerInterface $consumer): StreamingConsumerInterface
    {
        return new CallbackStreamingConsumer(
            static function (StreamInterface $stream) use ($consumer) {
                $successful = false;

                do {
                    $result = $stream->consume(StreamingConsumer::optional($consumer));
                    yield from $result;
                    $successful = $successful || StreamingConsumer::isValid($result);
                } while (StreamingConsumer::isValid($result));

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

    public static function streamify(ConsumerInterface $consumer): StreamingConsumerInterface
    {
        return $consumer instanceof StreamingConsumerInterface ? $consumer : new StreamifiedConsumer($consumer);
    }
}
