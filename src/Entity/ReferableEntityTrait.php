<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait ReferableEntityTrait
{
    /**
     * Identifier for stop coming from provider
     *
     * @ORM\Column(type="string")
     * @ORM\Id
     */
    private $id;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }
}