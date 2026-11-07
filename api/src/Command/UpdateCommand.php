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

use App\DataImport\ImportGraph;
use App\Message\UpdateDataSources;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class UpdateCommand extends Command
{
    public function __construct(
        private readonly ImportGraph $graph,
        private readonly MessageBusInterface $bus
    ) {
        parent::__construct('app:update');
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addOption(
            'async',
            'a',
            InputOption::VALUE_NONE,
            'Run in worker process via message queue.'
        );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('async')) {
            $this->bus->dispatch(new UpdateDataSources());
            $output->writeln("Update request sent to message queue.");

            return Command::SUCCESS;
        }

        $import = $this->graph->runSynchronously();

        $output->writeln(sprintf('Import %s %s.', $import->getId(), $import->isSucceeded() ? 'succeeded' : 'failed'));
        foreach ($this->graph->countTasks($import) as $status => $count) {
            $output->writeln(sprintf('  %s: %d', $status, $count));
        }
        $output->writeln(sprintf('Peak memory: %d MB', memory_get_peak_usage(true) >> 20));

        return $import->isSucceeded() ? Command::SUCCESS : Command::FAILURE;
    }
}
