<?php

namespace App\Command;

use App\Context\FederationContext;
use App\Service\FederatedConnectionService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;

class FederationDisconnectCommand extends Command
{
    protected static $defaultName = 'federation:disconnect';
    protected static $defaultDescription = 'Disconnect this node into the federation network.';

    private FederationContext $federationContext;
    private FederatedConnectionService $federatedConnectionService;

    public function __construct(FederationContext $federationContext, FederatedConnectionService $federatedConnectionService)
    {
        parent::__construct(self::$defaultName);

        $this->federationContext = $federationContext;
        $this->federatedConnectionService = $federatedConnectionService;
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

        if (!$this->federationContext->isConnected()) {
            $io->error("This command can be called only in connected federated context.");

            return Command::FAILURE;
        }

        try {
            $this->federatedConnectionService->disconnect();

            $io->success(
                sprintf(
                    "Successfully disconnected %s from the federation.",
                    $this->federationContext->getConnectionId()->toRfc4122()
                )
            );

            return Command::SUCCESS;
        } catch (HttpExceptionInterface $exception) {
            $io->error("Transport Error: ".$exception->getMessage());

            return Command::FAILURE;
        }
    }
}
