<?php

namespace App\Modifier;

use App\Exception\InvalidArgumentException;
use App\Modifier\Modifier;
use App\Service\IterableUtils;

class IdFilter implements Modifier
{
    /** @var string|array */
    private $id;

    public function __construct($id)
    {
        if (!is_iterable($id) && !is_string($id)) {
            throw InvalidArgumentException::invalidType('id', $id, ['string', 'array']);
        }

        $this->id = is_iterable($id) ? IterableUtils::toArray($id) : $id;
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
