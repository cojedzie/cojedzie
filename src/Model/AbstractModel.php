<?php


namespace App\Model;


abstract class AbstractModel implements Fillable, Referable
{
    use FillTrait;

    private $id;

    protected function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

}