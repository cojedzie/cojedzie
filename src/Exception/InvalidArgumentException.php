<?php

namespace App\Exception;

class InvalidArgumentException extends \InvalidArgumentException
{
    public static function invalidType($parameter, $value, array $expected = [])
    {
        return new static(
            sprintf('Expected %s to be of type: %s. %s given.', $parameter, implode(', ', $expected), gettype($value))
        );
    }
}
