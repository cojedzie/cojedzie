<?php

namespace App\Subscriber;

use App\Context\FederationContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class FederationHeadersSubscriber implements EventSubscriberInterface
{
    const CONNECTION_ID_HEADER = 'X-CoJedzie-Connection-Id';
    const SERVER_ID_HEADER = 'X-CoJedzie-Server-Id';

    private FederationContext $federationContext;

    public function __construct(FederationContext $federationContext)
    {
        $this->federationContext = $federationContext;
    }
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onResponse'
        ];
    }

    public function onResponse(ResponseEvent $event)
    {
        // Headers should only be added on federated servers
        if (!$this->federationContext->isFederated()) {
            return;
        }

        $response = $event->getResponse();

        $response->headers->set(self::SERVER_ID_HEADER, $this->federationContext->getServerId()->toRfc4122());
        $response->headers->set(self::CONNECTION_ID_HEADER, $this->federationContext->getConnectionId()->toRfc4122());
    }
}
