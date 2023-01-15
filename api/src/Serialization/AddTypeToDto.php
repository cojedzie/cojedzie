<?php

namespace App\Serialization;

use App\Dto\Dto;
use App\Event\PostNormalizationEvent;
use App\Service\ContentTypeResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddTypeToDto implements EventSubscriberInterface
{
    public function __construct(
        private readonly ContentTypeResolver $contentTypeResolver,
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            PostNormalizationEvent::class => 'addContentTypeToDto',
        ];
    }

    public function addContentTypeToDto(PostNormalizationEvent $event)
    {
        $object = $event->getData();

        // We care only about DTOs
        if (!$object instanceof Dto) {
            return;
        }

        $mime = $this->contentTypeResolver->getContentTypeForObject($object);

        if (!$mime) {
            return;
        }

        $normalized          = $event->getNormalized();
        $normalized['$type'] = $mime;

        $event->setNormalized($normalized);
    }
}
