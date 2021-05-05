<?php

namespace App\Service;

use App\DataConverter\Converter;
use App\Entity\Federation\FederatedConnectionEntity;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Mercure\Update;

class FederatedConnectionUpdateFactory
{
    const TOPIC = 'network/nodes';

    const EVENT_NODE_JOINED  = 'node-joined';
    const EVENT_NODE_LEFT    = 'node-left';
    const EVENT_NODE_SUSPEND = 'node-suspend';
    const EVENT_NODE_RESUME  = 'node-resume';

    private SerializerInterface $serializer;
    private Converter $converter;

    public function __construct(SerializerInterface $serializer, Converter $converter)
    {
        $this->serializer = $serializer;
        $this->converter = $converter;
    }

    public function createNodeJoinedUpdate(FederatedConnectionEntity $connection)
    {
        return $this->createUpdate(self::EVENT_NODE_JOINED, $connection);
    }

    public function createNodeLeftUpdate(FederatedConnectionEntity $connection)
    {
        return $this->createUpdate(self::EVENT_NODE_LEFT, $connection);
    }

    public function createNodeSuspendUpdate(FederatedConnectionEntity $connection)
    {
        return $this->createUpdate(self::EVENT_NODE_SUSPEND, $connection);
    }

    public function createNodeResumeUpdate(FederatedConnectionEntity $connection)
    {
        return $this->createUpdate(self::EVENT_NODE_RESUME, $connection);
    }

    private function createUpdate($event, FederatedConnectionEntity $connection)
    {
        return new Update(
            self::TOPIC,
            $this->serializer->serialize([
                'event' => $event,
                'node'  => $this->converter->convert($connection),
            ], 'json'),
        );
    }
}
