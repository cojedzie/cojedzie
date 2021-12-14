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

namespace App\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Illuminate\Support\Collection;

final class IterableUtils
{
    public static function toArray(iterable $iterable): array
    {
        if (is_array($iterable)) {
            return $iterable;
        }

        return iterator_to_array($iterable);
    }

    public static function toArrayCollection(iterable $iterable): ArrayCollection
    {
        return new ArrayCollection(static::toArray($iterable));
    }

    public static function toCollection(iterable $iterable): Collection
    {
        return collect($iterable);
    }

    public static function batch(iterable $iterable, int $size): \Generator
    {
        $batch = [];

        foreach ($iterable as $key => $item) {
            $batch[$key] = $item;

            if (count($batch) >= $size) {
                yield $batch;
                $batch = [];
            }
        }

        yield $batch;
    }
}
