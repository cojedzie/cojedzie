<?php

namespace App\Command;

use App\Context\FederationContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FederationDisconnectCommand extends Command
{
    protected static $defaultName = 'federation:disconnect';
    protected static $defaultDescription = 'Disconnect this node into the federation network.';

    private FederationContext $federationContext;

    public function __construct(FederationContext $federationContext)
    {
        parent::__construct(self::$defaultName);

        $this->federationContext = $federationContext;
    }

    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->federationContext->isConnected()) {
            $io->error("This command can be called only in connected federated context.");

            return Command::FAILURE;
        }

        // todo: make actual api call

        $io->success(
            sprintf(
                "Successfully disconnected %s from the federation.",
                $this->federationContext->getConnectionId()->toRfc4122()
            )
        );

        return Command::SUCCESS;
    }
}
