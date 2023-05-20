<?php

namespace App\Dto;

use Symfony\Component\Serializer\Annotation\SerializedName;

interface HasRefs
{
    #[SerializedName('$refs')]
    public function getRefs(): Refs;
}
