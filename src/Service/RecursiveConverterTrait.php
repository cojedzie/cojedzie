<?php

namespace App\Service;

trait RecursiveConverterTrait
{
    /**
     * @var Converter
     */
    private $parent;

    public function setParent(?Converter $converter)
    {
        $this->parent = $converter;
    }

    public function getParent(): ?Converter
    {
        return $this->parent;
    }
}
