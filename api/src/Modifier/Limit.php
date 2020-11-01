<?php

namespace App\Modifier;

class Limit implements Modifier
{
    private $offset;
    private $count;

    public function __construct(int $offset = 0, ?int $count = null)
    {
        $this->offset = $offset;
        $this->count  = $count;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function getCount()
    {
        return $this->count;
    }

    public static function count(int $count)
    {
        return new static(0, $count);
    }
}
