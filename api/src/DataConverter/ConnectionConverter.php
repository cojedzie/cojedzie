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

namespace App\DataConverter;

use App\Entity\Federation\FederatedConnectionEntity;
use App\Model\DTO;
use App\Model\Federation\Node;
use App\Model\Status\Aggregated;
use JMS\Serializer\SerializerInterface;

class ConnectionConverter implements Converter
{
    public function __construct(private readonly SerializerInterface $serializer)
    {
    }

    public function convert($entity, string $type)
    {
        /** @var FederatedConnectionEntity $entity */

        $status = $entity->getLastStatus() ?
            $this->serializer->deserialize($entity->getLastStatus(), Aggregated::class, 'json')
            : null;

        return Node::createFromArray([
            'id'        => $entity->getId(),
            'url'       => $entity->getUrl(),
            'endpoints' => $status ? $status->getEndpoints() : collect(),
            'type'      => Node::TYPE_FEDERATED,
        ]);
    }

    public function supports($entity, string $type)
    {
        return $entity instanceof FederatedConnectionEntity
            && ($type === DTO::class || is_subclass_of($type, DTO::class, true));
    }
}
