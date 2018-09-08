<?php

namespace App\Model;

trait ReferableTrait
{
    /**
     * Identifier coming from provider service
     * @var string
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

    public static function reference($id)
    {
        if (!is_array($id)) {
            $id = ['id' => $id];
        }

        $result = new static();
        $result->fill($id);

        return $result;
    }
}