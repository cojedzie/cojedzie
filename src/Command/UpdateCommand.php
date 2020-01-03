<?php

namespace App\Command;

use App\Service\DataUpdater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends Command
{
    /** @var DataUpdater */
    private $updater;

    /**
     * UpdateCommand constructor.
     *
     * @param $updater
     */
    public function __construct(DataUpdater $updater)
    {
        parent::__construct('app:update');
        $this->updater = $updater;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->updater->update($output);
    }
}