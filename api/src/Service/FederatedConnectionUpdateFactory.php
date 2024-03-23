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
use App\Dto\Dto;
use App\Entity\Federation\FederatedConnectionEntity;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;

class FederatedConnectionUpdateFactory
{
    final public const string TOPIC = 'network/nodes';

    final public const string EVENT_NODE_JOINED  = 'node-joined';
    final public const string EVENT_NODE_LEFT    = 'node-left';
    final public const string EVENT_NODE_SUSPEND = 'node-suspend';
    final public const string EVENT_NODE_RESUME  = 'node-resume';

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly Converter $converter
    ) {
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
                'node'  => $this->converter->convert($connection, Dto::class),
            ], 'json'),
        );
    }
}
