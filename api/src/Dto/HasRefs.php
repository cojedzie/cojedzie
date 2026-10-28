<?php

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

interface HasRefs
{
    #[SerializedName('$refs')]
    public function getRefs(): Refs;
}
