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

namespace App\Utility;

use Ds\Deque;
use Ds\Map;
use Ds\Sequence;

final class CollectionUtils
{
    /**
     * Groups items using given function.
     *
     * @template T
     * @template U of Sequence<int, T>
     *
     * @psalm-param iterable<T> $collection
     * @psalm-param Closure(T): string $grouping
     * @psalm-param class-string<U> $container
     *
     * @return Map<string, U<T>>
     */
    public static function groupBy(iterable $collection, callable $grouping, string $container = Deque::class): Map
    {
        $result = new Map();

        foreach ($collection as $value) {
            $group = $grouping($value);

            if (!$result->hasKey($group)) {
                $result[$group] = new $container();
            }

            $result[$group]->push($value);
        }

        return $result;
    }

    /**
     * @template T
     * @template U
     *
     * @psalm-param iterable<T> $collection
     * @psalm-param Closure(T, string|int): U|U[] $map
     *
     * @return Sequence<U>
     */
    public static function flatMap(iterable $collection, callable $map): Sequence
    {
        $result = new ($collection instanceof Sequence ? $collection::class : Deque::class)();

        foreach ($collection as $key => $item) {
            $mapped = $map($item, $key);
            $result->push(...$mapped);
        }

        return $result;
    }
}
