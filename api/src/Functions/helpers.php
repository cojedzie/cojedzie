<?php
/*
 * Copyright (C) 2021 Kacper Donat
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

namespace App\Functions;

use JetBrains\PhpStorm\Pure;

function encapsulate($value)
{
    return match (true) {
        is_array($value)    => $value,
        is_iterable($value) => iterator_to_array($value),
        default             => [$value],
    };
}

/**
 * @template T
 *
 * @param T $value
 * @param callable<void, T> $callback
 *
 * @return mixed
 */
function setup($value, $callback)
{
    $callback($value);
    return $value;
}

function memoize(&$value, $callback)
{
    return $value
        ?? $value = $callback($value);
}

function class_name($object): string
{
    return (new \ReflectionObject($object))->getName();
}

/**
 * @template T
 *
 * @psalm-param T $value
 * @psalm-param T $min
 * @psalm-param T $max
 *
 * @psalm-return T
 */
#[Pure]
function clamp($value, $min, $max)
{
    return min($max, max($min, $value));
}
