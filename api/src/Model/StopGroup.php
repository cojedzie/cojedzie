<?php

namespace App\Model;

use Illuminate\Support\Collection;
use JMS\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

/**
 * Class StopGroup
 *
 * @package App\Model
 */
class StopGroup
{
    /**
     * Name of stop group.
     * @OA\Property(example="Jasień PKM")
     * @Serializer\Type("string")
     * @var string
     */
    private $name;

    /**
     * All stops in group.
     * @var Collection|Stop[]
     * @OA\Property(
     *     type="array",
     *     @OA\Items(ref=@Model(type=Stop::class, groups={"Default", "WithDestinations"}))
     * )
     */
    private $stops;

    public function __construct()
    {
        $this->stops = new Collection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setStops($stops)
    {
        $this->stops = new Collection($stops);
    }

    public function getStops()
    {
        return $this->stops;
    }
}
