<?php

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
    /** @var DataUpdater */
    private $updater;
    /** @var MessageBusInterface */
    private $bus;

    public function __construct(DataUpdater $updater, MessageBusInterface $bus)
    {
        parent::__construct('app:update');

        $this->updater = $updater;
        $this->bus = $bus;
    }

    protected function configure()
    {
        $this->addOption(
            'async', 'a',
            InputOption::VALUE_NONE,
            'Run in worker process via message queue.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('async')) {
            $this->bus->dispatch(new UpdateDataMessage());
            $output->writeln("Update request sent to message queue.");
        } else {
            $this->updater->update($output);
        }

        return Command::SUCCESS;
    }
}
