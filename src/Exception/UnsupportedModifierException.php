<?php

namespace App\Exception;

use App\Modifier\Modifier;
use App\Provider\Repository;

class UnsupportedModifierException extends \LogicException
{
    public static function createFromModifier(Modifier $modifier, Repository $repository)
    {
        return new static(sprintf("Modifier %s is not supported by %s.", get_class($modifier), get_class($repository)));
    }
}
