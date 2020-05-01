<?php

namespace App\Model;

use Carbon\Carbon;
use JMS\Serializer\Annotation as Serializer;
use Swagger\Annotations as SWG;

class Provider implements Fillable, Referable
{
    use FillTrait;

    /**
     * Short identifier of provider, ex. "trojmiasto"
     * @SWG\Property(example="trojmiasto")
     * @Serializer\Type("string")
     * @var string
     */
    private $id;

    /**
     * Full name of the provider, ex. "MZKZG Trójmiasto"
     * @SWG\Property(example="MZKZG Trójmiasto")
     * @Serializer\Type("string")
     * @var string
     */
    private $name;

    /**
     * Short name of the provider for easier identification, ex. "Trójmiasto" or "Warszawa"
     * @SWG\Property(example="Trójmiasto")
     * @Serializer\Type("string")
     * @var string
     */
    private $shortName;

    /**
     * Attribution to be presented for this provider, can contain HTML tags.
     * @SWG\Property(example="Copyright by XYZ inc.")
     * @Serializer\Type("string")
     * @var string|null
     */
    private $attribution;

    /**
     * Time when data was last synchronized with this provider.
     * @Serializer\Type("Carbon")
     * @var Carbon|null
     */
    private $lastUpdate;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getShortName(): string
    {
        return $this->shortName;
    }

    public function setShortName(string $shortName): void
    {
        $this->shortName = $shortName;
    }

    public function getAttribution(): ?string
    {
        return $this->attribution;
    }

    public function setAttribution(?string $attribution): void
    {
        $this->attribution = $attribution;
    }

    public function getLastUpdate(): ?Carbon
    {
        return $this->lastUpdate;
    }

    public function setLastUpdate(?Carbon $lastUpdate): void
    {
        $this->lastUpdate = $lastUpdate;
    }
}
