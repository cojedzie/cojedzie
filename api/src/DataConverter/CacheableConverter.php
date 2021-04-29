<?php

namespace App\DataConverter;

use Symfony\Contracts\Service\ResetInterface;

interface CacheableConverter extends Converter, ResetInterface
{
}
