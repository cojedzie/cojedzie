<?php

namespace App\MessageHandler;

use App\Entity\Federation\FederatedConnectionEntity;
use App\Message\CheckConnectionMessage;
use App\Service\FederatedConnectionChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class CheckConnectionMessageHandler implements MessageHandlerInterface
{
    private FederatedConnectionChecker $checker;
    private EntityManagerInterface $manager;

    public function __construct(FederatedConnectionChecker $checker, EntityManagerInterface $manager)
    {
        $this->checker = $checker;
        $this->manager = $manager;
    }

    public function __invoke(CheckConnectionMessage $message)
    {
        $connection = $this->manager
            ->getRepository(FederatedConnectionEntity::class)
            ->find($message->getConnectionId())
            ;

        $this->checker->check($connection);
    }
}
