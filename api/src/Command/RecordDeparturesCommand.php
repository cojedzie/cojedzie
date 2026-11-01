<?php
/*
 * Copyright (C) 2026 Kacper Donat
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

declare(strict_types=1);

namespace App\Command;

use App\DepartureHistory\DepartureRecorder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:record-departures', description: 'Record a snapshot of every provider\'s departures into the departure history now.')]
class RecordDeparturesCommand extends Command
{
    public function __construct(
        private readonly DepartureRecorder $recorder,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->recorder->isEnabled()) {
            $io->error('Departure history is disabled: COJEDZIE_CLICKHOUSE_URL is empty.');
            return Command::FAILURE;
        }

        if (!$this->recorder->record()) {
            $io->warning('Not every provider was recorded; failures are logged as warnings (use -v to see them).');

            return Command::FAILURE;
        }

        $io->success('Departures recorded.');

        return Command::SUCCESS;
    }
}
