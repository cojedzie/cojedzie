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

class FederationCleanupCommand extends Command
{
    protected static $defaultName        = 'federation:cleanup';
    protected static $defaultDescription = 'Cleanup closed connections from database.';

    public function __construct(
        private readonly EntityManagerInterface $manager,
    ) {
        parent::__construct(self::$defaultName);
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
        $count      = $repository->deleteClosedConnections();

        $io->success(sprintf("Removed %d closed connections.", $count));

        return Command::SUCCESS;
    }
}
