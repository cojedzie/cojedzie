<?php

namespace App\Command;

use App\Repository\FederatedConnectionEntityRepository;
use App\Utility\CustomSentrySampleRateInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

class FederationConnectionsDescribeCommand extends Command implements CustomSentrySampleRateInterface
{
    use FederationSampleRateTrait;

    protected static $defaultName        = 'federation:connections:describe';
    protected static $defaultDescription = 'Describe federation connection';

    public function __construct(
        private readonly FederatedConnectionEntityRepository $federatedConnectionRepository,
        private readonly SerializerInterface $serializer
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('id', InputArgument::REQUIRED, 'Connection id')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $id         = Uuid::fromString($input->getArgument('id'));
        $connection = $this->federatedConnectionRepository->find($id);

        if (!$connection) {
            $io->error(sprintf('Connection "%s" does not exist.', $id->toRfc4122()));
            return Command::FAILURE;
        }

        $serialized = $this->serializer->serialize($connection, 'json');

        $io->write($serialized);

        return Command::SUCCESS;
    }
}
