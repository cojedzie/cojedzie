<?php

namespace App\Event;

use ArrayObject;

class PostNormalizationEvent
{
    public function __construct(
        private array|ArrayObject $normalized,
        private readonly mixed $data,
        private readonly mixed $format,
        private readonly array $context,
    ) {
    }

    public function getNormalized(): array|ArrayObject
    {
        return $this->normalized;
    }

    public function setNormalized(array|ArrayObject $normalized): void
    {
        $this->normalized = $normalized;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getFormat(): mixed
    {
        return $this->format;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
