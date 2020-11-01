<?php

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;
use Swagger\Annotations as SWG;

interface Referable
{
    public function getId();
}