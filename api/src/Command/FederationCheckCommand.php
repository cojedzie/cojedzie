<?php
/*
 * Copyright (C) 2021 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Command;

use App\Entity\Federation\FederatedConnectionEntity;
use App\Repository\FederatedConnectionEntityRepository;
use App\Service\FederatedConnectionChecker;
use App\Utility\CustomSentrySampleRateInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

class FederationCheckCommand extends Command implements CustomSentrySampleRateInterface
{
    protected static $defaultName        = 'federation:check';
    protected static $defaultDescription = 'Get list of all federated servers.';

    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly FederatedConnectionChecker $checker
    ) {
        parent::__construct(self::$defaultName);
    }

    #[\Override]
    protected function configure()
    {
        $this->setDescription(self::$defaultDescription);

        $this->addArgument('connection', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Connections to check');
        $this->addOption('force', 'f', InputOption::VALUE_OPTIONAL, 'Check even if before next check time');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $ids   = array_map([Uuid::class, 'fromString'], $input->getArgument('connection'));
        $force = (bool) $input->getOption('force');

        /** @var FederatedConnectionEntityRepository $repository */
        $repository  = $this->manager->getRepository(FederatedConnectionEntity::class);
        $connections = !empty($ids)
            ? $repository->findConnectionsById($ids)
            : $repository->findAllConnectionsToCheck();

        foreach ($connections as $connection) {
            $io->writeln(sprintf("Checking connection id %s", $connection->getId()->toRfc4122()));
            $this->checker->check($connection, $force);
        }

        return Command::SUCCESS;
    }

    #[\Override]
    public function getSentrySampleRate(): float
    {
        return 0.0;
    }
}
