<?php

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;
use Swagger\Annotations as SWG;

class Location
{
    /**
     * Locations longitude.
     * @Serializer\Type("float")
     * @Serializer\SerializedName("lng")
     */
    private $longitude;

    /**
     * Locations latitude.
     * @Serializer\Type("float")
     * @Serializer\SerializedName("lat")
     * @SWG\Property()
     */
    private $latitude;

    public function __construct(float $longitude = 0.0, float $latitude = 0.0)
    {
        $this->set($longitude, $latitude);
    }

    public function getLongitude()
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): void
    {
        $this->longitude = $longitude;
    }

    public function getLatitude()
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): void
    {
        $this->latitude = $latitude;
    }

    public function set(float $longitude, float $latitude)
    {
        $this->setLongitude($longitude);
        $this->setLatitude($latitude);
    }
}