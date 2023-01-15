<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ServiceLocator;

class HandlerProviderFactory
{
    public function __construct(
        private readonly ServiceLocator $handlerLocator,
    ) {
    }

    public function createHandlerProvider(array $configuration = [])
    {
        return new HandlerProvider($this->handlerLocator, $configuration);
    }
}
