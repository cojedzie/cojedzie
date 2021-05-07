<?php

namespace App\Model\Status;

use App\Model\DTO;
use App\Model\Fillable;
use App\Model\FillTrait;
use JMS\Serializer\Annotation as Serializer;
use OpenApi\Annotations as OA;

class Endpoint implements Fillable, DTO
{
    use FillTrait;

    /**
     * Name of the endpoint, machine readable
     *
     * @Serializer\Type("string")
     * @OA\Property(type="string", example="v1_provider_list")
     */
    private string $name;

    /**
     * Route template for that endpoint.
     *
     * @Serializer\Type("string")
     * @OA\Property(type="string", example="/api/v1/providers")
     */
    private string $template;

    /**
     * Maximum version supported for that endpoint.
     *
     * @Serializer\Type("string")
     * @OA\Property(type="string", format="version", example="1.0")
     */
    private string $version;

    /**
     * Methods supported for that endpoint.
     *
     * @Serializer\Type("array<string>")
     * @OA\Property(
     *     type="array",
     *     @OA\Items(type="string", enum={"GET", "POST", "DELETE", "PUT", "PATCH"})
     * )
     */
    private array $methods;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function setMethods(array $methods): void
    {
        $this->methods = $methods;
    }
}
