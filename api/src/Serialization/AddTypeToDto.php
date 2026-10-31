<?php

namespace App\Serialization;

use App\Dto\Dto;
use App\Event\PostNormalizationEvent;
use App\Service\ContentTypeResolver;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class AddTypeToDto
{
    public function __construct(
        private readonly ContentTypeResolver $contentTypeResolver,
    ) {
    }

    #[AsEventListener(event: PostNormalizationEvent::class)]
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
