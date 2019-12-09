<?php

namespace App\Subscriber;

use App\Model\JustReference;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;

class JustReferenceSerializationSubscriber implements EventSubscriberInterface
{
    public function onPreSerialize(PreSerializeEvent $event)
    {
        $object = $event->getObject();
        $type = $event->getType();

        $event->setType(get_parent_class($object), $type['params']);
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            ['event' => 'serializer.pre_serialize', 'method' => 'onPreSerialize', 'interface' => JustReference::class],
        ];
    }
}