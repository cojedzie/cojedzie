<?php

namespace App\Dto\Links;

use App\Dto\Links;

class CollectionLinks implements Links
{
    public function __construct(
        public readonly Link $self,
    ) {
    }
}
