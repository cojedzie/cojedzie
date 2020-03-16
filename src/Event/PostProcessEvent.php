<?php

namespace App\Event;

use App\Modifier\Modifier;
use App\Provider\Repository;

class PostProcessEvent extends HandleModifierEvent
{
    private $data;

    public function __construct($data, Modifier $modifier, Repository $repository, array $meta = [])
    {
        parent::__construct($modifier, $repository, $meta);
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data): void
    {
        $this->data = $data;
    }
}
