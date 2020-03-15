<?php

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
