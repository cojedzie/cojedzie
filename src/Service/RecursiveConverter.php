<?php

namespace App\Service;

interface RecursiveConverter extends Converter
{
    public function setParent(?Converter $converter);
    public function getParent(): ?Converter;
}
