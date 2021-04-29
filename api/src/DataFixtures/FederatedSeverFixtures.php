<?php

namespace App\DataFixtures;

use App\Entity\Federation\FederatedServerEntity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class FederatedSeverFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $server = FederatedServerEntity::createFromArray([
            'email'      => 'maintainer@example.com',
            'allowedUrl' => 'http://federated:8080',
            'maintainer' => 'John Doe <john@example.com>',
            'secret'     => password_hash('notarealsecretatall', PASSWORD_BCRYPT),
        ]);
        $manager->persist($server);

        $manager->flush();
    }
}
