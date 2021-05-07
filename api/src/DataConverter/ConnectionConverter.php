<?php

namespace App\DataConverter;

use App\Entity\Federation\FederatedConnectionEntity;
use App\Model\DTO;
use App\Model\Federation\Node;
use App\Model\Status\Aggregated;
use JMS\Serializer\SerializerInterface;

class ConnectionConverter implements Converter
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
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
