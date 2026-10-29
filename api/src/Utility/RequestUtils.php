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

use App\Filter\Binding\Http\Exception\InvalidParameterException;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;

final class RequestUtils
{
    private function __construct()
    {
    }

    public static function get(Request $request, string $parameter, array $from, mixed $default = null)
    {
        foreach ($from as $source) {
            /** @var InputBag $bag */
            $bag = $request->$source;

            if ($bag->has($parameter)) {
                return $bag->all()[$parameter];
            }
        }

        return $default;
    }

    /**
     * Values of a parameter sent either comma-separated (stop=a,b) or as a list (stop[]=a&stop[]=b).
     *
     * @throws InvalidParameterException when the list holds anything but single values
     *
     * @return list<string>|null null when the parameter is missing
     */
    public static function getList(Request $request, string $parameter, array $from): ?array
    {
        $value = self::get($request, $parameter, $from);

        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            return explode(',', (string) $value);
        }

        foreach ($value as $item) {
            if (!is_scalar($item)) {
                throw InvalidParameterException::invalid($parameter, 'expected a value or a list of values');
            }
        }

        return array_map(strval(...), array_values($value));
    }
}
