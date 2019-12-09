<?php

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;

class Operator implements Fillable, Referable
{
    use FillTrait, ReferableTrait;

    /**
     * Describes operator name
     * @Serializer\Type("string")
     * @var string
     */
    private $name;

    /**
     * Contact email to operator
     * @Serializer\Type("string")
     * @var string|null
     */
    private $email;

    /**
     * URL of operators page
     * @Serializer\Type("string")
     * @var string|null
     */
    private $url;

    /**
     * Contact phone to operator
     * @Serializer\Type("string")
     * @var string|null
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