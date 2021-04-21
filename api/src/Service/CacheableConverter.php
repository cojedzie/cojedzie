<?php

namespace App\Service;

use Symfony\Contracts\Service\ResetInterface;

interface CacheableConverter extends Converter, ResetInterface
{
}
