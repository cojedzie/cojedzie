<?php

namespace App\Dto;

use Ds\Set;

class MessageRefs implements Refs
{
    public function __construct(
        public readonly Set $stops = new Set(),
        public readonly Set $lines = new Set(),
    ) {
    }

    public function getStops(): Set
    {
        return $this->stops;
    }

    public function getLines(): Set
    {
        return $this->lines;
    }
}
