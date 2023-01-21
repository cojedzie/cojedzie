<?php

namespace App\Dto\Links;

use Symfony\Component\HttpFoundation\Request;

class Link
{
    public function __construct(
        public readonly string $href,
        public readonly string $method = Request::METHOD_GET,
    ) {
    }
}
