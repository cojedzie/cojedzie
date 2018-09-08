<?php

namespace App\Model;

class Operator implements Fillable, Referable
{
    use FillTrait, ReferableTrait;

    /**
     * Describes operator name
     * @var string
     */
    private $name;

    /**
     * Contact email to operator
     * @var string|null
     */
    private $email;

    /**
     * URL of operators page
     * @var string|null
     */
    private $url;

    /**
     * Contact phone to operator
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