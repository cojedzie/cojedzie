<?php

namespace App\Serialization\Normalizer;

use App\Dto\Links;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class LinksNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public function __construct(
        private readonly DtoNormalizer $normalizer
    ) {
    }

    #[\Override]
    public function normalize($object, string $format = null, array $context = []): array|bool|string|int|float|null|\ArrayObject
    {
        return $this->normalizer->normalize($object, $format, [
            ...$context,
            AbstractObjectNormalizer::SKIP_NULL_VALUES       => true,
            AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => true,
        ]);
    }

    #[\Override]
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof Links;
    }

    #[\Override]
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
