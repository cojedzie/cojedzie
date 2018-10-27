<?php

namespace App\Entity;

use App\Model\Fillable;
use App\Model\FillTrait;
use App\Model\Referable;
use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("provider")
 */
class ProviderEntity implements Fillable, Referable
{
    use ReferableEntityTrait, FillTrait;

    /**
     * Provider short name, for example. ZTM Gdańsk
     *
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * Class that handles that provider
     *
     * @ORM\Column(type="string")
     */
    private $class;

    /**
     * Time and date of last data update
     *
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $updateDate;

    /**
     * ProviderEntity constructor.
     */
    public function __construct()
    {
        $this->updateDate = Carbon::now();
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function setClass($class): void
    {
        $this->class = $class;
    }

    /**
     * @return Carbon
     */
    public function getUpdateDate()
    {
        return Carbon::instance($this->updateDate);
    }
}