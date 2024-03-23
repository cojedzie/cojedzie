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

    #[\Override]
    public function normalize($object, string $format = null, array $context = []): array|\ArrayObject
    {
        $normalized = $this->decorated->normalize($object, $format, $context);

        /** @var PostNormalizationEvent $event */
        $event = $this->dispatcher->dispatch(new PostNormalizationEvent(
            normalized: $normalized,
            data: $object,
            format: $format,
            context: $context
        ));

        $processed = $event->getNormalized();

        foreach ($processed as $key => $value) {
            // Skip empty magic properties
            if ($key[0] === '$' && $this->isEmpty($value)) {
                unset($processed[$key]);
            }
        }

        return $processed;
    }

    #[\Override]
    public function supportsNormalization($data, string $format = null): bool
    {
        return $this->decorated->supportsNormalization($data, $format);
    }

    #[\Override]
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->decorated->setSerializer($serializer);
    }

    private function isEmpty(mixed $value)
    {
        if (is_countable($value)) {
            return count($value) == 0;
        }

        return empty($value);
    }
}
