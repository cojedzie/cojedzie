<?php

namespace App\Model;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

trait ReferableTrait
{
    /**
     * Identifier coming from provider service
     * @Serializer\Type("string")
     * @Serializer\Groups({"Default", "Identity", "Minimal"})
     * @var string
     */
    private $id;

    /**
     * @return string
     */
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
