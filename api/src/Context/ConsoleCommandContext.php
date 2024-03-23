<?php

namespace App\Context;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleCommandContext implements EventSubscriberInterface
{
    private ?Command $currentCommand = null;

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'handleCommandEvent',
        ];
    }

    public function handleCommandEvent(ConsoleCommandEvent $event): void
    {
        $this->setCurrentCommand($event->getCommand());
    }

    public function getCurrentCommand(): ?Command
    {
        return $this->currentCommand;
    }

    public function setCurrentCommand(Command $currentCommand): void
    {
        $this->currentCommand = $currentCommand;
    }
}
