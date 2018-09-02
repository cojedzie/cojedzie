<?php


namespace App\Model;


trait ReferenceTrait
{
    abstract protected function setId($id);

    public static function reference($id)
    {
        $reference = new static();
        $reference->setId($id);

        return $reference;
    }
}