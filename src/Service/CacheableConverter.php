<?php

namespace App\Service;

interface CacheableConverter extends Converter
{
    public function flushCache();
}
