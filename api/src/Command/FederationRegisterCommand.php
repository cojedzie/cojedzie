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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\ByteString;
use Symfony\Component\Uid\Uuid;

class FederationRegisterCommand extends Command
{
    protected static $defaultName        = 'federation:register';
    protected static $defaultDescription = 'Register new federated server';

    public function __construct(
        private readonly EntityManagerInterface $manager
    ) {
        parent::__construct(self::$defaultName);
    }

    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('email', InputArgument::REQUIRED, 'Contact e-mail for registered federated server.')
            ->addArgument('allowed-url', InputArgument::REQUIRED, 'Allowed base URL, could be regex pattern.')
            ->addOption('maintainer', 'm', InputOption::VALUE_OPTIONAL, 'Name of the maintainer for this server.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $secret = ByteString::fromRandom(24);

        $server = FederatedServerEntity::createFromArray([
            'id'         => Uuid::v4(),
            'email'      => $input->getArgument('email'),
            'allowedUrl' => $input->getArgument('allowed-url'),
            'maintainer' => $input->getOption('maintainer'),
            'secret'     => password_hash($secret->toString(), PASSWORD_BCRYPT),
        ]);

        $this->manager->persist($server);
        $this->manager->flush();

        $io->success(sprintf(
            'Server registered, Server ID: %s, Server Secret: %s',
            $server->getId()->toRfc4122(),
            $secret->toString()
        ));

        return Command::SUCCESS;
    }
}
