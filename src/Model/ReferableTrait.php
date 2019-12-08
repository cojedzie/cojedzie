<?php

namespace App\Model;

use Swagger\Annotations as SWG;

trait ReferableTrait
{
    /**
     * Identifier coming from provider service
     * @var string
     *
     * @SWG\Property(example="1045")
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