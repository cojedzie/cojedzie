<?php

namespace App\Serialization;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * @Annotation
 * @Target({"PROPERTY","METHOD","ANNOTATION"})
 */
class SerializeAs
{
    /** @var array<string, string> @Required() */
    public $map;
}
