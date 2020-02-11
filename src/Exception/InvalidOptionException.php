<?php

namespace App\Exception;

class InvalidOptionException extends \InvalidArgumentException
{
    public static function invalidType($parameter, $value, array $expected = [])
    {
        return new \InvalidArgumentException(
            sprintf('Expected %s to be of type: %s. %s given.', $parameter, implode(', ', $expected), gettype($value))
        );
    }
}
