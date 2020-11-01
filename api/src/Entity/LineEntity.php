<?php

namespace App\Entity;

use App\Model\Fillable;
use App\Model\FillTrait;
use App\Model\Referable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("line")
 */
class LineEntity implements Fillable, Entity
{
    use FillTrait, ReferableEntityTrait, ProviderReferenceTrait;

    /**
     * Line symbol, for example '10', or 'A'
     * @var string
     *
     * @ORM\Column(type="string", length=16)
     */
    private $symbol;

    /**
     * Line type tram, bus or whatever.
     * @var string
     *
     * @ORM\Column(type="string", length=20)
     */
    private $type;

    /**
     * Is line considered as fast line?
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $fast = false;

    /**
     * Is line considered as night line?
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $night = false;

    /**
     * Line operator
     *
     * @var OperatorEntity
     * @ORM\ManyToOne(targetEntity=OperatorEntity::class)
     */
    private $operator;

    /**
     * @ORM\OneToMany(targetEntity=TrackEntity::class, mappedBy="line")
     */
    private $tracks;

    public function __construct()
    {
        $this->tracks = new ArrayCollection();
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): void
    {
        $this->symbol = $symbol;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function isFast(): bool
    {
        return $this->fast;
    }

    public function setFast(bool $fast): void
    {
        $this->fast = $fast;
    }

    public function isNight(): bool
    {
        return $this->night;
    }

    public function setNight(bool $night): void
    {
        $this->night = $night;
    }

    public function getTracks()
    {
        return $this->tracks;
    }

    /**
     * @return OperatorEntity
     */
    public function getOperator(): ?OperatorEntity
    {
        return $this->operator;
    }

    /**
     * @param OperatorEntity $operator
     */
    public function setOperator(OperatorEntity $operator): void
    {
        $this->operator = $operator;
    }
}