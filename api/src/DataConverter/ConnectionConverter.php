<?php

namespace App\DataConverter;

use App\Entity\Federation\FederatedConnectionEntity;
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

    public function convert($entity)
    {
        /** @var FederatedConnectionEntity $entity */

        $status = $this->serializer->deserialize($entity->getLastStatus(), Aggregated::class, 'json');

        return Node::createFromArray([
            'id'        => $entity->getId(),
            'url'       => $entity->getUrl(),
            'endpoints' => $status->getEndpoints(),
        ]);
    }

    public function supports($entity)
    {
        return $entity instanceof FederatedConnectionEntity;
    }
}
