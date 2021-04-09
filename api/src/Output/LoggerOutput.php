<?php


namespace App\Output;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\Output;

class LoggerOutput extends Output
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        LoggerInterface $logger,
        ?int $verbosity = self::VERBOSITY_NORMAL,
        bool $decorated = false,
        OutputFormatterInterface $formatter = null
    ) {
        parent::__construct($verbosity, $decorated, $formatter);

        $this->logger = $logger;
    }

    protected function doWrite(string $message, bool $newline)
    {
        $this->logger->info($message);
    }
}
