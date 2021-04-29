<?php

namespace App\Model\Federation;

use App\Model\Fillable;
use App\Model\FillTrait;
use App\Model\Status\Endpoint;
use Illuminate\Support\Collection;
use JMS\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\Uid\Uuid;

class Node implements Fillable
{
    use FillTrait;

    /**
     * Unique identifier for node.
     *
     * @Serializer\Type("uuid")
     * @OA\Property(type="string", example="a022a57b-866c-4f59-a3cf-2271d958552c")
     */
    private Uuid $id;

    /**
     * Base URL address for this particular connection.
     * @Serializer\Type("string")
     * @OA\Property(type="string", format="url", example="https://cojedzie.pl")
     *
     */
    private string $url;

    /**
     * All endpoints offered by this node.
     *
     * @Serializer\Type("Collection")
     * @OA\Property(type="array", @OA\Items(ref=@Model(type=Endpoint::class)))
     *
     * @var Collection<Endpoint>
     */
    private Collection $endpoints;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): void
    {
        $this->id = $id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getEndpoints(): Collection
    {
        return $this->endpoints;
    }

    public function setEndpoints(Collection $endpoints): void
    {
        $this->endpoints = $endpoints;
    }
}
