<?php


namespace App\Model;


class Stop implements Fillable, Referable
{
    use FillTrait, ReferenceTrait;

    /**
     * Some unique stop identification
     * @var mixed
     */
    private $id;

    /**
     * Stop name
     * @var string
     */
    private $name;

    /**
     * Optional stop description
     * @var string|null
     */
    private $description;

    /**
     * Optional stop variant - for example number of shed
     * @var string|null
     */
    private $variant;

    /**
     * Tuple of lat and long
     * @var [float, float]
     */
    private $location;

    /**
     * True if stop is available only on demand
     * @var bool
     */
    private $onDemand;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return null|string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param null|string $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param mixed $location
     */
    public function setLocation($location): void
    {
        $this->location = $location;
    }

    /**
     * @return null|string
     */
    public function getVariant(): ?string
    {
        return $this->variant;
    }

    /**
     * @param null|string $variant
     */
    public function setVariant(?string $variant): void
    {
        $this->variant = $variant;
    }

    /**
     * @return bool
     */
    public function isOnDemand(): bool
    {
        return $this->onDemand;
    }

    /**
     * @param bool $onDemand
     */
    public function setOnDemand(bool $onDemand): void
    {
        $this->onDemand = $onDemand;
    }
}