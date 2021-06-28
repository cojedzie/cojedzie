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

use App\Entity\Federation\FederatedServerEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FederationServersCommand extends Command
{
    protected static $defaultName = 'federation:servers';
    protected static $defaultDescription = 'Get list of all federated servers.';

    private EntityManagerInterface $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        parent::__construct(self::$defaultName);
        $this->manager = $manager;
    }

    protected function configure()
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $repository = $this->manager->getRepository(FederatedServerEntity::class);

        /** @var FederatedServerEntity[] $servers */
        $servers = $repository->findAll();

        $io->table(
            ['Server ID', 'Allowed URL', 'Maintainer'],
            array_map(
                fn (FederatedServerEntity $server) => [
                    $server->getId()->toRfc4122(),
                    $server->getAllowedUrl(),
                    $server->getMaintainer() ? sprintf("%s <%s>", $server->getMaintainer(), $server->getEmail()) : $server->getEmail(),
                ],
                $servers
            )
        );

        return Command::SUCCESS;
    }
}
