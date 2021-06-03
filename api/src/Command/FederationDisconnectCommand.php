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
use App\Service\FederatedConnectionService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;

class FederationDisconnectCommand extends Command
{
    protected static $defaultName = 'federation:disconnect';
    protected static $defaultDescription = 'Disconnect this node into the federation network.';

    private FederationContext $federationContext;
    private FederatedConnectionService $federatedConnectionService;

    public function __construct(FederationContext $federationContext, FederatedConnectionService $federatedConnectionService)
    {
        parent::__construct(self::$defaultName);

        $this->federationContext = $federationContext;
        $this->federatedConnectionService = $federatedConnectionService;
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

        if (!$this->federationContext->isConnected()) {
            $io->error("This command can be called only in connected federated context.");

            return Command::FAILURE;
        }

        try {
            $this->federatedConnectionService->disconnect();

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
