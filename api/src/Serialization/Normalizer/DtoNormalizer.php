<?php

namespace App\Serialization\Normalizer;

use App\Event\PostNormalizationEvent;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer as SymfonyObjectNormalizer;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DtoNormalizer implements NormalizerInterface, SerializerAwareInterface
{
    public function __construct(
        private readonly SymfonyObjectNormalizer $decorated,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    public function normalize($object, string $format = null, array $context = []): array
    {
        $normalized = $this->decorated->normalize($object, $format, $context);

        /** @var PostNormalizationEvent $event */
        $event = $this->dispatcher->dispatch(new PostNormalizationEvent(
            normalized: $normalized,
            data: $object,
            format: $format,
            context: $context
        ));

        return $event->getNormalized();
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $this->decorated->supportsNormalization($data, $format);
    }

    public function setSerializer(SerializerInterface $serializer)
    {
        $this->decorated->setSerializer($serializer);
    }
}
