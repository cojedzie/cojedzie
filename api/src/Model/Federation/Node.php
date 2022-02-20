<?php
/*
 * Copyright (C) 2021 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Model\Federation;

use App\Model\DTO;
use App\Model\Fillable;
use App\Model\FillTrait;
use App\Model\Status\Endpoint;
use Illuminate\Support\Collection;
use JMS\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\Uid\Uuid;

class Node implements Fillable, DTO
{
    use FillTrait;

    final const TYPE_HUB       = 'hub';
    final const TYPE_FEDERATED = 'federated';

    final const TYPES = [ self::TYPE_HUB, self::TYPE_FEDERATED ];

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
     * Type of the node.
     *
     * @Serializer\Type("string")
     * @OA\Property(type="string", format="url", example=Node::TYPE_HUB, enum=Node::TYPES)
     */
    private string $type;

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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }
}
