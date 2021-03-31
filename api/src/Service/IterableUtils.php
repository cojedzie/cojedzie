<?php

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
}
