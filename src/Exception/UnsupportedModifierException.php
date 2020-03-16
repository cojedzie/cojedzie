<?php

namespace App\Exception;

use App\Modifier\Modifier;

class UnsupportedModifierException extends \LogicException
{
    public static function createFromModifier(Modifier $modifier)
    {
        return new static(sprintf("Modifier %s is not supported.", get_class($modifier)));
    }
}
