<?php

namespace App\Exception;

use App\Modifiers\Modifier;
use App\Provider\Repository;

class UnsupportedModifierException extends \Exception
{
    public static function createFromModifier(Modifier $modifier, Repository $repository)
    {
        return new static(sprintf("Modifier %s is not supported by %s.", get_class($modifier), get_class($repository)));
    }
}
