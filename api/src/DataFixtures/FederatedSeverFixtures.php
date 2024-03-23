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

namespace App\DataFixtures;

use App\Entity\Federation\FederatedServerEntity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

class FederatedSeverFixtures extends Fixture
{
    #[\Override]
    public function load(ObjectManager $manager)
    {
        $server = FederatedServerEntity::createFromArray([
            'id'         => Uuid::fromString('a7cd192a-3dca-4fc8-b35d-91f2d6e10632'),
            'email'      => 'maintainer@example.com',
            'allowedUrl' => 'http://federated:8080',
            'maintainer' => 'John Doe',
            'secret'     => password_hash('notarealsecretatall', PASSWORD_BCRYPT),
        ]);
        $manager->persist($server);

        $manager->flush();
    }
}
