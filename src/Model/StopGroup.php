<?php

namespace App\Model;

use Tightenco\Collect\Support\Collection;

class StopGroup extends Collection
{
    /**
     * Name of stop group
     * @var string
     */
    private $name;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}