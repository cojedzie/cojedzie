<?php

namespace App\Entity;

use App\Model\Fillable;
use App\Model\FillTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("operator")
 */
class OperatorEntity implements Fillable, Entity
{
    use ProviderReferenceTrait, FillTrait, ReferableEntityTrait;

    /**
     * Describes operator name
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * Contact email to operator
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $email;

    /**
     * URL of operators page
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $url;

    /**
     * Contact phone to operator
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $phone;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }
}