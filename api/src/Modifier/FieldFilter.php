<?php

namespace App\Modifier;

class FieldFilter implements Modifier
{
    private $field;
    private $value;
    private $operator;

    public function __construct(string $field, $value, string $operator = '=')
    {
        $this->field    = $field;
        $this->value    = $value;
        $this->operator = $operator;
    }

    public static function contains(string $field, string $value)
    {
        return new static($field, "%$value%", 'LIKE');
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }
}
