<?php

namespace App\Subscriber;

use App\Service\Converter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ResetInterface;

class RequestCleanupSubscriber implements EventSubscriberInterface
{
    /** @var ContainerInterface */
    private ContainerInterface $container;
    private Converter $converter;

    public function __construct(ContainerInterface $container, Converter $converter)
    {
        $this->container = $container;
        $this->converter = $converter;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::TERMINATE => ['onTerminate']
        ];
    }

    public function onTerminate(TerminateEvent $event)
    {
        $this->container->get('doctrine')->reset();

        if ($this->converter instanceof ResetInterface) {
            $this->converter->reset();
        }
    }
}
