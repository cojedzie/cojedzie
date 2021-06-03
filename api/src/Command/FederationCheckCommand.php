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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FederationCheckCommand extends Command
{
    protected static $defaultName = 'federation:check';
    protected static $defaultDescription = 'Get list of all federated servers.';

    private EntityManagerInterface $manager;
    private FederatedConnectionChecker $checker;

    public function __construct(EntityManagerInterface $manager, FederatedConnectionChecker $checker)
    {
        parent::__construct(self::$defaultName);
        $this->manager = $manager;
        $this->checker = $checker;
    }

    protected function configure()
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var FederatedConnectionEntityRepository $repository */
        $repository = $this->manager->getRepository(FederatedConnectionEntity::class);
        $connections = $repository->findAllConnectionsToCheck();

        foreach ($connections as $connection) {
            $io->writeln(sprintf("Checking connection id %s", $connection->getId()->toRfc4122()));
            $this->checker->check($connection);
        }

        return Command::SUCCESS;
    }
}
