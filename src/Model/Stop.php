<?php

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Tightenco\Collect\Support\Collection;

/**
 * Class Stop
 *
 * @package App\Model
 */
class Stop implements Referable, Fillable
{
    use FillTrait, ReferableTrait;

    /**
     * Stop name
     *
     * @var string
     * @SWG\Property(example="Jasień PKM")
     * @Serializer\Type("string")
     */
    private $name;

    /**
     * Optional stop description, should not be longer than 255 chars.
     *
     * @var string|null
     * @Serializer\Type("string")
     */
    private $description;

    /**
     * Optional stop variant - for example number of shed.
     *
     * @var string|null
     * @SWG\Property(example="01")
     * @Serializer\Type("string")
     */
    private $variant;

    /**
     * Optional stop location in form of latitude and longitude
     *
     * @var ?Location
     * @Serializer\Type(Location::class)
     */
    private $location;

    /**
     * True if stop is available only on demand
     *
     * @var bool
     * @Serializer\Type("bool")
     * @SWG\Property(example=false)
     */
    private $onDemand = false;

    /**
     * Name of group that this stop is part of.
     *
     * @Serializer\Type("string")
     * @SWG\Property(example="Jasień PKM")
     * @var string|null
     */
    private $group;

    /**
     * Collection of possible destination stops.
     *
     * @Serializer\Groups({"WithDestinations"})
     * @Serializer\Type("Collection")
     * @SWG\Property(type="array", @SWG\Items(ref=@Model(type=Stop::class, groups={"Default"})))
     *
     * @var Collection<Stop>
     */
    private $destinations;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getVariant(): ?string
    {
        return $this->variant;
    }

    public function setVariant(?string $variant): void
    {
        $this->variant = $variant;
    }

    public function isOnDemand(): bool
    {
        return $this->onDemand;
    }

    public function setOnDemand(bool $onDemand): void
    {
        $this->onDemand = $onDemand;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function setLocation(?Location $location): void
    {
        $this->location = $location;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function setGroup(?string $group): void
    {
        $this->group = $group;
    }

    public function getDestinations(): Collection
    {
        return $this->destinations;
    }

    public function setDestinations(Collection $destinations): void
    {
        $this->destinations = $destinations;
    }
}
