<?php

namespace App\Service;

use App\Exception\UnsupportedModifierException;
use App\Modifier\Modifier;
use Symfony\Component\DependencyInjection\ServiceLocator;

class HandlerProvider
{
    private $configuration = [];
    private $handlerLocator;

    public function __construct(ServiceLocator $handlerLocator)
    {
        $this->handlerLocator = $handlerLocator;
    }

    public function loadConfiguration(array $providers)
    {
        $this->configuration = $providers;
    }

    public function get(Modifier $modifier)
    {
        $class = get_class($modifier);

        if (!array_key_exists($class, $this->configuration)) {
            throw UnsupportedModifierException::createFromModifier($modifier);
        }

        $handler = $this->configuration[$class];

        if (is_callable($handler)) {
            $handler = $handler($modifier);
        }

        if (is_string($handler)) {
            return $this->handlerLocator->get($handler);
        }

        return $handler;
    }
}
