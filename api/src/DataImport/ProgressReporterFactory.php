<?php
/*
 * Copyright (C) 2021 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 2 of the License, or
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

namespace App\DataImport;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class ProgressReporterFactory
{
    private ?ConsoleOutputInterface $output = null;
    private bool $isMessageConsumer         = false;
    private bool $isConsole                 = false;

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function create(): ProgressReporterInterface
    {
        if ($this->isConsole && !$this->isMessageConsumer) {
            return new ConsoleProgressReporter($this->output);
        }

        return new LoggerProgressReporter($this->logger);
    }

    #[AsEventListener(event: ConsoleEvents::COMMAND)]
    public function handleConsoleCommandEvent(ConsoleCommandEvent $event)
    {
        if ($event->getOutput() instanceof ConsoleOutputInterface) {
            /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
            $this->output    = $event->getOutput();
            $this->isConsole = true;
        }

        $this->isMessageConsumer = str_starts_with((string) $event->getCommand()->getName(), 'messenger:');
    }
}
