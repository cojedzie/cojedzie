<?php

namespace App\Serialization;

use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class Serializer implements SerializerInterface, NormalizerInterface
{
    public function __construct(
        private readonly SerializerInterface & NormalizerInterface $decorated,
    ) {
    }

    public function serialize($data, string $format, array $context = [])
    {
        $this->setDefaultContextProperties($context);

        return $this->decorated->serialize($data, $format, $context);
    }

    public function deserialize($data, string $type, string $format, array $context = [])
    {
        $this->setDefaultContextProperties($context);

        return $this->decorated->deserialize($data, $type, $format, $context);
    }

    private function setDefaultContextProperties(array &$context)
    {
        $context[AbstractNormalizer::IGNORED_ATTRIBUTES] = [
            '__isInitialized__',
            '__initializer__',
            '__cloner__',
        ];

        $context[AbstractObjectNormalizer::SKIP_NULL_VALUES] = false;

        $context[AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER] = fn ($entity) => ['id' => $entity->getId()];
    }

    public function normalize($object, string $format = null, array $context = []): array
    {
        return $this->decorated->normalize($object, $format, $context);
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $this->decorated->supportsNormalization($data, $format);
    }
}
