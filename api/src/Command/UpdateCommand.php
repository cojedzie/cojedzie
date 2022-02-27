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

use App\Message\UpdateDataMessage;
use App\Service\DataUpdater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class UpdateCommand extends Command
{
    protected static $defaultName = 'app:update';

    public function __construct(
        private readonly DataUpdater $updater,
        private readonly MessageBusInterface $bus
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addOption(
            'async',
            'a',
            InputOption::VALUE_NONE,
            'Run in worker process via message queue.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('async')) {
            $this->bus->dispatch(new UpdateDataMessage());
            $output->writeln("Update request sent to message queue.");
        } else {
            $this->updater->update();
        }

        return Command::SUCCESS;
    }
}
