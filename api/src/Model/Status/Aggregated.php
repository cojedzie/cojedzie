<?php

namespace App\Model\Status;

use App\Model\Fillable;
use App\Model\FillTrait;
use Illuminate\Support\Collection;
use JMS\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

class Aggregated implements Fillable
{
    use FillTrait;

    /**
     * Time status for this node.
     *
     * @OA\Property(ref=@Model(type=Time::class))
     * @Serializer\Type(Time::class)
     */
    private Time $time;

    /**
     * All endpoints defined for this node.
     *
     * @OA\Property(type="array", @OA\Items(ref=@Model(type=Endpoint::class)))
     * @Serializer\Type("Collection<App\Model\Status\Endpoint>")
     *
     * @var Collection<Endpoint>
     */
    private Collection $endpoints;

    public function getEndpoints(): Collection
    {
        return $this->endpoints;
    }

    public function setEndpoints(Collection $endpoints): void
    {
        $this->endpoints = $endpoints;
    }

    public function getTime(): Time
    {
        return $this->time;
    }

    public function setTime(Time $time): void
    {
        $this->time = $time;
    }
}
