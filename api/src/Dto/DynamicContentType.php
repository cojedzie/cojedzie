<?php

namespace App\Dto;

use App\Service\ContentTypeResolver;

interface DynamicContentType
{
    public function getContentType(ContentTypeResolver $resolver): ?string;
}
