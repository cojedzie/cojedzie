<?php


namespace App\Subscriber;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class JSONFormatSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => "onRequest",
        ];
    }

    public function onRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        if (!$request->attributes->has('_format')) {
            $request->attributes->set('_format', 'json');
        }
    }
}
