<?php

namespace App\DataConverter;

interface Converter
{
    public function convert($entity);
    public function supports($entity);
}
