<?php

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;
use Swagger\Annotations as SWG;

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
     * @var string
     * @SWG\Property(example="Jasień PKM")
     * @Serializer\Type("string")
     */
    private $name;

    /**
     * Optional stop description, should not be longer than 255 chars.
     * @var string|null
     * @Serializer\Type("string")
     */
    private $description;

    /**
     * Optional stop variant - for example number of shed.
     * @var string|null
     * @SWG\Property(example="01")
     * @Serializer\Type("string")
     */
    private $variant;

    /**
     * Latitude of stop
     * @var float|null
     * @Serializer\Exclude()
     */
    private $latitude;

    /**
     * Longitude of stop
     * @var float|null
     * @Serializer\Exclude()
     */
    private $longitude;

    /**
     * True if stop is available only on demand
     * @var bool
     * @Serializer\Type("bool")
     * @SWG\Property(example=false)
     */
    private $onDemand = false;

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

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): void
    {
        $this->latitude = $latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): void
    {
        $this->longitude = $longitude;
    }

    /**
     * @return string[]
     * @Serializer\VirtualProperty()
     * @Serializer\Type("array<string>")
     * @SWG\Property(type="array", @SWG\Items(type="string", example="1"))
     */
    public function getLocation(): array
    {
        return [ $this->latitude, $this->longitude ];
    }

    public function setLocation(array $location)
    {
        [$this->latitude, $this->longitude] = $location;
    }

    public function isOnDemand(): bool
    {
        return $this->onDemand;
    }

    public function setOnDemand(bool $onDemand): void
    {
        $this->onDemand = $onDemand;
    }
}