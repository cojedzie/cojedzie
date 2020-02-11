<?php

namespace App\Event;

use App\Modifiers\Modifier;
use App\Provider\Repository;

class HandleModifierEvent
{
    private $repository;
    private $modifier;
    private $meta = [];

    public function __construct(Modifier $modifier, Repository $repository, array $meta = [])
    {
        $this->repository = $repository;
        $this->modifier   = $modifier;
        $this->meta       = $meta;
    }

    public function getModifier(): Modifier
    {
        return $this->modifier;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }
}
