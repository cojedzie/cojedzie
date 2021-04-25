<?php

namespace App\Command;

use App\Context\FederationContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FederationConnectCommand extends Command
{
    const ENDPOINT_CONNECT = '/api/v1/federation/connections';

    protected static $defaultName = 'federation:connect';
    protected static $defaultDescription = 'Connect this node into the federation network.';

    private FederationContext $federationContext;
    private HttpClientInterface $http;

    public function __construct(FederationContext $federationContext, HttpClientInterface $http)
    {
        parent::__construct(self::$defaultName);

        $this->federationContext = $federationContext;
        $this->http = $http;
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
            $response = $this->http->request(
                'POST',
                $this->federationContext->getHubBaseUrl().static::ENDPOINT_CONNECT,
                ['body' => [
                    'server_id' => $this->federationContext->getServerId()->toRfc4122(),
                    'url'       => $this->federationContext->getAdvertisedUrl(),
                ]]
            );

            if ($response->getStatusCode() !== Response::HTTP_CREATED) {
                $io->error($response->getContent());
                return Command::FAILURE;
            }

            $connection = $response->toArray();
            $output->writeln($connection['connection_id']);

            return Command::SUCCESS;
        } catch (HttpExceptionInterface $exception) {
            $io->error("Transport Error: ".$exception->getMessage());
            return Command::FAILURE;
        }
    }
}
