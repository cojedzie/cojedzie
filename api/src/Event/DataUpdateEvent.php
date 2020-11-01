<?php

namespace App\Event;

use App\Service\DataUpdater;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\EventDispatcher\Event;

class DataUpdateEvent extends Event
{
    const NAME = DataUpdater::UPDATE_EVENT;

    private $output;

    public function __construct(?OutputInterface $output = null)
    {
        $this->output = $output ?? new NullOutput();
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }
}