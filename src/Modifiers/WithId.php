<?php

namespace App\Modifiers;

use App\Exception\InvalidOptionException;
use App\Modifiers\Modifier;

class WithId implements Modifier
{
    /** @var string|array */
    private $id;

    public function __construct($id)
    {
        if (!is_iterable($id) && !is_string($id)) {
            throw InvalidOptionException::invalidType('id', $id, ['string', 'array']);
        }

        $this->id = $id instanceof \Traversable ? iterator_to_array($id) : $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function isMultiple()
    {
        return is_array($this->id);
    }
}
