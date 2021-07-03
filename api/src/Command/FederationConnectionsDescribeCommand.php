<?php

namespace App\Command;

use App\Repository\FederatedConnectionEntityRepository;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

class FederationConnectionsDescribeCommand extends Command
{
    protected static $defaultName = 'federation:connections:describe';
    protected static $defaultDescription = 'Describe federation connection';

    private FederatedConnectionEntityRepository $federatedConnectionRepository;
    private SerializerInterface $serializer;

    public function __construct(
        FederatedConnectionEntityRepository $federatedConnectionRepository,
        SerializerInterface $serializer
    ) {
        parent::__construct();

        $this->federatedConnectionRepository = $federatedConnectionRepository;
        $this->serializer = $serializer;
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

        $id = Uuid::fromString($input->getArgument('id'));
        $connection = $this->federatedConnectionRepository->find($id);

        if (!$connection) {
            $io->error(sprintf('Connection "%s" does not exist.', $id->toRfc4122()));
            return Command::FAILURE;
        }

        $serialized = $this->serializer->serialize(
            $connection,
            'json',
            SerializationContext::create()->setGroups(['Default'])
        );

        $io->write($serialized);

        return Command::SUCCESS;
    }
}
