<?php

namespace App\Command;

use App\Entity\Federation\FederatedServerEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\ByteString;

class FederationRegisterCommand extends Command
{
    protected static $defaultName = 'federation:register';
    protected static $defaultDescription = 'Register new federated server';

    private EntityManagerInterface $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        parent::__construct(self::$defaultName);
        $this->manager = $manager;
    }


    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('email', InputArgument::REQUIRED, 'Contact e-mail for registered federated server.')
            ->addArgument('allowed-url', InputArgument::REQUIRED, 'Allowed base URL, could be regex pattern.')
            ->addOption('maintainer', 'm', InputOption::VALUE_OPTIONAL, 'Name of the maintainer for this server.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $secret = ByteString::fromRandom(24);

        $server = FederatedServerEntity::createFromArray([
            'email'      => $input->getArgument('email'),
            'allowedUrl' => $input->getArgument('allowed-url'),
            'maintainer' => $input->getOption('maintainer'),
            'secret'     => password_hash($secret->toString(), PASSWORD_BCRYPT),
        ]);

        $this->manager->persist($server);
        $this->manager->flush();

        $io->success(sprintf(
            'Server registered, Server ID: %s, Server Secret: %s',
            $server->getId()->toRfc4122(),
            $secret->toString()
        ));

        return Command::SUCCESS;
    }
}
