<?php

namespace App\Modifier;

use App\Exception\InvalidArgumentException;
use App\Model\Referable;
use App\Service\IterableUtils;

class RelatedFilter implements Modifier
{
    private $relationship;
    private $reference;

    public function __construct($reference, ?string $relation = null)
    {
        if (!is_iterable($reference) && !$reference instanceof Referable) {
            throw InvalidArgumentException::invalidType('object', $reference, [Referable::class, 'iterable']);
        }

        $this->reference    = is_iterable($reference) ? IterableUtils::toArray($reference) : $reference;
        $this->relationship = $relation ?: get_class($reference);
    }

    public function getRelationship(): string
    {
        return $this->relationship;
    }

    public function getRelated()
    {
        return $this->reference;
    }

    public function isMultiple()
    {
        return is_array($this->reference);
    }
}
