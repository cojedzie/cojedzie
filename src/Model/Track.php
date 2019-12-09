<?php

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Tightenco\Collect\Support\Collection;

class Track implements Referable, Fillable
{
    use ReferableTrait, FillTrait;

    /**
     * Line variant describing track, for example 'a'
     * @Serializer\Type("string")
     * @SWG\Property(example="a")
     * @var string|null
     *
     */
    private $variant;

    /**
     * Track description
     * @Serializer\Type("string")
     * @var string|null
     */
    private $description;

    /**
     * Line reference
     * @SWG\Property(ref=@Model(type=Line::class, groups={"Default"}))
     * @var Line
     */
    private $line;

    /**
     * Stops in track
     * @var Stop[]|Collection
     * @Serializer\Type("Collection")
     * @SWG\Property(type="array", @SWG\Items(ref=@Model(type=Stop::class)))
     */
    private $stops;

    /**
     * Track constructor.
     */
    public function __construct()
    {
        $this->setStops([]);
    }

    public function getVariant(): ?string
    {
        return $this->variant;
    }

    public function setVariant(?string $variant): void
    {
        $this->variant = $variant;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getLine(): Line
    {
        return $this->line;
    }

    public function setLine(Line $line): void
    {
        $this->line = $line;
    }

    public function getStops(): Collection
    {
        return $this->stops;
    }

    public function setStops($stops = [])
    {
        return $this->stops = collect($stops);
    }
}