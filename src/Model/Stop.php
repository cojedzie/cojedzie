<?php

namespace App\Model;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Normalizer\NormalizableInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Tightenco\Collect\Support\Arr;

class Stop implements Referable, Fillable, NormalizableInterface
{
    use FillTrait, ReferableTrait;

    /**
     * Stop name
     * @var string
     */
    private $name;

    /**
     * Optional stop description, should not be longer than 255 chars
     * @var string|null
     */
    private $description;

    /**
     * Optional stop variant - for example number of shed
     * @var string|null
     */
    private $variant;

    /**
     * Latitude of stop
     * @var float|null
     */
    private $latitude;

    /**
     * Longitude of stop
     * @var float|null
     */
    private $longitude;

    /**
     * True if stop is available only on demand
     * @var bool
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

    /** @Groups({"hidden"}) */
    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): void
    {
        $this->latitude = $latitude;
    }

    /** @Groups({"hidden"}) */
    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): void
    {
        $this->longitude = $longitude;
    }

    public function getLocation(): array
    {
        return [ $this->latitude, $this->longitude ];
    }

    public function setLocation(array $location)
    {
        list($this->latitude, $this->longitude) = $location;
    }

    public function isOnDemand(): bool
    {
        return $this->onDemand;
    }

    public function setOnDemand(bool $onDemand): void
    {
        $this->onDemand = $onDemand;
    }

    public function normalize(NormalizerInterface $normalizer, $format = null, array $context = [])
    {
        return Arr::except($normalizer->normalize($this), ['latitude', 'longitude']);
    }
}