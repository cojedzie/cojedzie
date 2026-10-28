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

declare(strict_types=1);

namespace App\Tests\Behat\Context;

use App\Entity\Federation\FederatedConnectionEntity;
use App\Entity\Federation\FederatedServerEntity;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Federated servers and their connections, as the hub stores them.
 */
final readonly class FederationContext implements Context
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    #[Given('a federated server :id allowed at :url with secret :secret')]
    public function aFederatedServer(string $id, string $url, string $secret): void
    {
        $server = new FederatedServerEntity();
        $server->fill([
            'id'         => Uuid::fromString($id),
            'email'      => 'maintainer@example.com',
            'allowedUrl' => $url,
            'secret'     => password_hash($secret, PASSWORD_BCRYPT),
        ]);

        $this->em->persist($server);
        $this->em->flush();
    }

    #[Given('the server :server has an open connection :id at :url')]
    public function theServerHasAnOpenConnection(string $server, string $id, string $url): void
    {
        $connection = new FederatedConnectionEntity();
        $connection->setServer($this->em->find(FederatedServerEntity::class, Uuid::fromString($server)));
        $connection->setUrl($url);
        $connection->setState(FederatedConnectionEntity::STATE_READY);
        $connection->setOpenedAt(Carbon::now());
        $connection->setNextCheck(Carbon::now()->addHour());

        $this->em->persist($connection);
        $this->em->flush();

        // connection ids are generated, scenarios refer to them by a known one
        $this->em->getConnection()->update(
            'federated_connection',
            ['id' => Uuid::fromString($id)],
            ['id' => $connection->getId()],
            ['id' => 'uuid'],
        );
        $this->em->clear();
    }
}
