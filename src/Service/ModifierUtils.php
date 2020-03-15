<?php

namespace App\Service;

use Kadet\Functional\Predicate;
use function Kadet\Functional\Predicates\instance;

final class ModifierUtils
{
    public static function get(iterable $modifiers, Predicate $predicate)
    {
        return collect($modifiers)->first($predicate);
    }

    public static function getOfType(iterable $modifiers, $class)
    {
        return self::get($modifiers, instance($class));
    }

    public static function hasAny(iterable $modifiers, Predicate $predicate)
    {
        return collect($modifiers)->contains($predicate);
    }

    public static function hasAnyOfType(iterable $modifiers, $class)
    {
        return collect($modifiers)->contains(instance($class));
    }
}
