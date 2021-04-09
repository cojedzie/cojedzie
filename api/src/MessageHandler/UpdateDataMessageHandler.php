<?php

namespace App\MessageHandler;

use App\Message\UpdateDataMessage;
use App\Output\LoggerOutput;
use App\Service\DataUpdater;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class UpdateDataMessageHandler implements MessageHandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var DataUpdater */
    private $updater;

    public function __construct(DataUpdater $updater)
    {
        $this->updater = $updater;
    }

    public function __invoke(UpdateDataMessage $message)
    {
        try {
            $this->updater->update(new LoggerOutput($this->logger));
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage(), [
                'backtrace' => $exception->getTraceAsString()
            ]);
        }
    }
}
