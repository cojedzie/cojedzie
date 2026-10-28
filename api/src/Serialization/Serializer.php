<?php

namespace App\Serialization;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class Serializer implements SerializerInterface, NormalizerInterface, DenormalizerInterface, EncoderInterface, DecoderInterface
{
    public function __construct(
        private readonly SerializerInterface&NormalizerInterface&DenormalizerInterface&EncoderInterface&DecoderInterface $decorated,
    ) {
    }

    #[\Override]
    public function serialize($data, string $format, array $context = []): string
    {
        $this->setDefaultContextProperties($context);

        return $this->decorated->serialize($data, $format, $context);
    }

    #[\Override]
    public function deserialize($data, string $type, string $format, array $context = []): mixed
    {
        $this->setDefaultContextProperties($context);

        return $this->decorated->deserialize($data, $type, $format, $context);
    }

    private function setDefaultContextProperties(array &$context)
    {
        $context[AbstractNormalizer::IGNORED_ATTRIBUTES] = [
            ...($context[AbstractNormalizer::IGNORED_ATTRIBUTES] ?? []),
            '__isInitialized__',
            '__initializer__',
            '__cloner__',
        ];

        $context[AbstractObjectNormalizer::SKIP_NULL_VALUES] ??= false;

        $context[AbstractNormalizer::GROUPS][] = 'default';

        $context[AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER] ??= fn ($entity) => ['id' => $entity->getId()];
    }

    #[\Override]
    public function normalize($object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        return $this->decorated->normalize($object, $format, $context);
    }

    #[\Override]
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $this->decorated->supportsNormalization($data, $format, $context);
    }

    #[\Override]
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        return $this->decorated->denormalize($data, $type, $format, $context);
    }

    #[\Override]
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $this->decorated->supportsDenormalization($data, $type, $format, $context);
    }

    #[\Override]
    public function getSupportedTypes(?string $format): array
    {
        return $this->decorated->getSupportedTypes($format);
    }

    #[\Override]
    public function encode(mixed $data, string $format, array $context = []): string
    {
        return $this->decorated->encode($data, $format, $context);
    }

    #[\Override]
    public function supportsEncoding(string $format, array $context = []): bool
    {
        // Symfony's serializers take the context, EncoderInterface doesn't declare it
        return $this->decorated->supportsEncoding($format, $context); // @phpstan-ignore arguments.count
    }

    #[\Override]
    public function decode(string $data, string $format, array $context = []): mixed
    {
        return $this->decorated->decode($data, $format, $context);
    }

    #[\Override]
    public function supportsDecoding(string $format, array $context = []): bool
    {
        // Symfony's serializers take the context, DecoderInterface doesn't declare it
        return $this->decorated->supportsDecoding($format, $context); // @phpstan-ignore arguments.count
    }
}
