<?php

namespace App\DataConverter;

interface Converter
{
    public function convert($entity, string $type);
    public function supports($entity, string $type);
}
