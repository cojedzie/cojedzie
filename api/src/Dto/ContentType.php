<?php

namespace App\Dto;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ContentType
{
    public function __construct(
        public readonly string $type
    ) {
    }
}
