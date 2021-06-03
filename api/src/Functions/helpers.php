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

function encapsulate($value)
{
    switch (true) {
        case is_array($value):
            return $value;
        case is_iterable($value):
            return iterator_to_array($value);
        default:
            return [ $value ];
    }
}

function setup($value, $callback)
{
    $callback($value);
    return $value;
}

function class_name($object): string
{
    return (new \ReflectionObject($object))->getName();
}
