<?php

namespace App\Command;

use App\Entity\Federation\FederatedServerEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FederationServersCommand extends Command
{
    protected static $defaultName = 'federation:servers';
    protected static $defaultDescription = 'Get list of all federated servers.';

    private EntityManagerInterface $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        parent::__construct(self::$defaultName);
        $this->manager = $manager;
    }


    protected function configure()
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $repository = $this->manager->getRepository(FederatedServerEntity::class);

        /** @var FederatedServerEntity[] $servers */
        $servers = $repository->findAll();

        $io->table(
            ['Server ID', 'Allowed URL', 'Maintainer'],
            array_map(
                fn (FederatedServerEntity $server) => [
                    $server->getId()->toRfc4122(),
                    $server->getAllowedUrl(),
                    $server->getMaintainer() ? sprintf("%s <%s>", $server->getMaintainer(), $server->getEmail()) : $server->getEmail(),
                ],
                $servers
            )
        );

        return Command::SUCCESS;
    }
}
