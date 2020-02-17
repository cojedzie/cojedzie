<?php

namespace App\Modifier;

use App\Model\Referable;

class RelatedFilter implements Modifier
{
    private $relationship;
    private $object;

    public function __construct(Referable $object, ?string $relation = null)
    {
        $this->object       = $object;
        $this->relationship = $relation ?: get_class($object);
    }

    public function getRelationship(): string
    {
        return $this->relationship;
    }

    public function getRelated(): Referable
    {
        return $this->object;
    }
}
