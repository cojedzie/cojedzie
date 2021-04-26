<?php

namespace App\Command;

use App\Entity\Federation\FederatedConnectionEntity;
use App\Repository\FederatedConnectionEntityRepository;
use App\Service\FederatedConnectionChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FederationCheckCommand extends Command
{
    protected static $defaultName = 'federation:check';
    protected static $defaultDescription = 'Get list of all federated servers.';

    private EntityManagerInterface $manager;
    private FederatedConnectionChecker $checker;

    public function __construct(EntityManagerInterface $manager, FederatedConnectionChecker $checker)
    {
        parent::__construct(self::$defaultName);
        $this->manager = $manager;
        $this->checker = $checker;
    }

    protected function configure()
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var FederatedConnectionEntityRepository $repository */
        $repository = $this->manager->getRepository(FederatedConnectionEntity::class);
        $connections = $repository->findAllConnectionsToCheck();

        foreach ($connections as $connection) {
            $io->writeln(sprintf("Checking connection id %s", $connection->getId()->toRfc4122()));
            $this->checker->check($connection);
        }

        return Command::SUCCESS;
    }
}
