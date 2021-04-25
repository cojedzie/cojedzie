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

class FederationDisconnectCommand extends Command
{
    const ENDPOINT_DISCONNECT = '/api/v1/federation/connections/{id}';

    protected static $defaultName = 'federation:disconnect';
    protected static $defaultDescription = 'Disconnect this node into the federation network.';

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
            ->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->federationContext->isConnected()) {
            $io->error("This command can be called only in connected federated context.");

            return Command::FAILURE;
        }

        try {
            $url = str_replace(
                '{id}',
                $this->federationContext->getConnectionId()->toRfc4122(),
                $this->federationContext->getHubBaseUrl().static::ENDPOINT_DISCONNECT
            );

            $io->write($url);

            $response = $this->http->request(
                'DELETE',
                str_replace(
                    '{id}',
                    $this->federationContext->getConnectionId()->toRfc4122(),
                    $this->federationContext->getHubBaseUrl().static::ENDPOINT_DISCONNECT
                )
            );


            if ($response->getStatusCode() !== Response::HTTP_OK) {
                $io->error($response->getContent());

                return Command::FAILURE;
            }

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
