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
use App\Utility\CustomSentrySampleRateInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;
use function App\Functions\class_name;

class FederationConnectCommand extends Command implements CustomSentrySampleRateInterface
{
    use FederationSampleRateTrait;
    protected static $defaultName        = 'federation:connect';
    protected static $defaultDescription = 'Connect this node into the federation network.';

    public function __construct(
        private readonly FederationContext $federationContext,
        private readonly FederatedConnectionService $federatedConnectionService
    ) {
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

        if (!$this->federationContext->isFederated()) {
            $io->error("This command can be called only in federated context.");
            return Command::FAILURE;
        }

        if ($this->federationContext->isConnected()) {
            $io->error("This command can be called only in not connected context.");
            return Command::FAILURE;
        }

        try {
            $connectionId = $this->federatedConnectionService->connect();
            $output->writeln($connectionId->toRfc4122());

            return Command::SUCCESS;
        } catch (HttpExceptionInterface $exception) {
            $io->error(sprintf("%s: %s", class_name($exception), $exception->getMessage()));
            return Command::FAILURE;
        }
    }
}
