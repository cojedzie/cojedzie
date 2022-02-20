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

use App\Context\FederationContext;
use App\Entity\Federation\FederatedConnectionEntity;
use App\Entity\Federation\FederatedServerEntity;
use App\Service\FederatedConnectionService;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;
use function App\Functions\class_name;

class FederationConnectionsListCommand extends Command
{
    protected static $defaultName = 'federation:connections:list';
    protected static $defaultDescription = 'List federated connections';

    public function __construct(private readonly EntityManagerInterface $manager)
    {
        parent::__construct(self::$defaultName);
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

        $repository = $this->manager->getRepository(FederatedConnectionEntity::class);

        $connectionsByServer =
            collect($repository->findAll())
            ->groupBy(fn (FederatedConnectionEntity $connection) => $connection->getServer()->getId()->toRfc4122());

        foreach ($connectionsByServer as $serverId => $connections) {
            $io->writeln("Server $serverId");
            $io->table(
                ['Connection ID', 'State', 'Url', 'Opened at', 'Closed at', 'Last check', 'Next check', 'Failures (Total)'],
                $connections
                    ->map(
                        fn (FederatedConnectionEntity $connection) => [
                            $connection->getId()->toRfc4122(),
                            $connection->getState(),
                            $connection->getUrl(),
                            $connection->getOpenedAt() ? $connection->getOpenedAt()->toRfc7231String() : "-",
                            $connection->getClosedAt() ? $connection->getClosedAt()->toRfc7231String() : "-",
                            $connection->getLastCheck() ? $connection->getLastCheck()->toRfc7231String() : "-",
                            $connection->getNextCheck() ? $connection->getNextCheck()->toRfc7231String() : "-",
                            sprintf("%d (%d)", $connection->getFailures(), $connection->getFailuresTotal()),
                        ]
                    )
                    ->toArray()
            );
        }

        return Command::SUCCESS;
    }
}
