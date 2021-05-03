<?php

namespace App\DataFixtures;

use App\Entity\Federation\FederatedServerEntity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

class FederatedSeverFixtures extends Fixture
{
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
