<?php

namespace App\Command;

use App\Context\FederationContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

class FederationConnectCommand extends Command
{
    protected static $defaultName = 'federation:connect';
    protected static $defaultDescription = 'Connect this node into the federation network.';

    private FederationContext $federationContext;

    public function __construct(FederationContext $federationContext)
    {
        parent::__construct(self::$defaultName);

        $this->federationContext = $federationContext;
    }

    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->federationContext->isFederated()) {
            $io->error("This command can be called only in federated context.");

            return Command::FAILURE;
        }

        if ($this->federationContext->isConnected()) {
            $io->error("This command can be called only in not connected context.");

            return Command::FAILURE;
        }

        // todo: make api call to create connection
        $output->writeln(Uuid::v4()->toRfc4122());

        return Command::SUCCESS;
    }
}
