<?php
/*
 * Copyright (C) 2026 Kacper Donat
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

namespace App\MessageHandler;

use App\Entity\Federation\FederatedConnectionEntity;
use App\Message\CleanupFederatedConnections;
use App\Repository\FederatedConnectionEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CleanupFederatedConnectionsMessageHandler
{
    public function __construct(
        private EntityManagerInterface $manager
    ) {
    }

    public function __invoke(CleanupFederatedConnections $message): void
    {
        /** @var FederatedConnectionEntityRepository $repository */
        $repository = $this->manager->getRepository(FederatedConnectionEntity::class);
        $repository->deleteClosedConnections();
    }
}
