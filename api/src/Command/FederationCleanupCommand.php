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

class FederationCleanupCommand extends Command
{
    protected static $defaultName = 'federation:cleanup';
    protected static $defaultDescription = 'Cleanup closed connections from database.';

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
        $count = $repository->deleteClosedConnections();

        $io->success(sprintf("Removed %d closed connections.", $count));

        return Command::SUCCESS;
    }
}
