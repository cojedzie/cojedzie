<?php

namespace App\Modifier;

class With implements Modifier
{
    private $relationship;

    public function __construct(string $relationship)
    {
        $this->relationship = $relationship;
    }

    public function getRelationship(): string
    {
        return $this->relationship;
    }
}
