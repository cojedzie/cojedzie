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

namespace App\Service;

use App\DataConverter\Converter;
use App\Entity\Federation\FederatedConnectionEntity;
use App\Model\DTO;
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
                'node'  => $this->converter->convert($connection, DTO::class),
            ], 'json'),
        );
    }
}
