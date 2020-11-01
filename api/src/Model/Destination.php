<?php

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Tightenco\Collect\Support\Collection;

class Destination implements Fillable
{
    use FillTrait;

    /**
     * Stop associated with destination.
     * @Serializer\Type(Stop::class)
     * @var Stop
     */
    private $stop;

    /**
     * @Serializer\Type("Collection")
     * @SWG\Property(type="array", @SWG\Items(ref=@Model(type=Line::class, groups={"Default"})))
     * @var Line[]|Collection<Line>
     */
    private $lines;

    public function __construct()
    {
        $this->lines = collect();
    }

    public function getStop(): Stop
    {
        return $this->stop;
    }

    public function setStop(Stop $stop): void
    {
        $this->stop = $stop;
    }

    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function setLines(iterable $lines): void
    {
        $this->lines = collect($lines);
    }
}
