<?php

namespace App\Command;

use App\Context\FederationContext;
use App\Service\FederatedConnectionService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;
use function App\Functions\class_name;

class FederationConnectCommand extends Command
{
    protected static $defaultName = 'federation:connect';
    protected static $defaultDescription = 'Connect this node into the federation network.';

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

        if (!$this->federationContext->isFederated()) {
            $io->error("This command can be called only in federated context.");
            return Command::FAILURE;
        }

        if ($this->federationContext->isConnected()) {
            $io->error("This command can be called only in not connected context.");
            return Command::FAILURE;
        }

        try {
            $connectionId = $this->federatedConnectionService->connect();
            $output->writeln($connectionId->toRfc4122());

            return Command::SUCCESS;
        } catch (HttpExceptionInterface $exception) {
            $io->error(sprintf("%s: %s", class_name($exception), $exception->getMessage()));
            return Command::FAILURE;
        }
    }
}
