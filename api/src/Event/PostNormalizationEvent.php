<?php

namespace App\Event;

class PostNormalizationEvent
{
    public function __construct(
        private array $normalized,
        private readonly mixed $data,
        private readonly mixed $format,
        private readonly array $context,
    ) {
    }

    public function getNormalized(): array
    {
        return $this->normalized;
    }

    public function setNormalized(array $normalized): void
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
