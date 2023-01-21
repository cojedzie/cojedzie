<?php

namespace App\Dto\Links;

class PaginationLinks extends CollectionLinks
{
    public function __construct(
        Link $self,
        public readonly ?Link $next = null,
        public readonly ?Link $prev = null,
        public readonly ?Link $first = null
    ) {
        parent::__construct($self);
    }
}
