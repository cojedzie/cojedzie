<?php

namespace App\Dto;

use Ds\Set;

class MessageRefs implements Refs
{
    public function __construct(
        public readonly CollectionResult $stops = new CollectionResult(),
        public readonly CollectionResult $lines = new CollectionResult(),
    ) {
    }

    public function getStops(): CollectionResult
    {
        return $this->stops;
    }

    public function getLines(): CollectionResult
    {
        return $this->lines;
    }
}
