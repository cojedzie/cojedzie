<?php

namespace App\Serialization\Normalizer;

use App\Dto\Links\Link;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class LinkNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly DtoNormalizer $normalizer
    ) {
    }

    #[\Override]
    public function normalize($object, ?string $format = null, array $context = []): array|bool|string|int|float|null|\ArrayObject
    {
        $base = $this->normalizer->normalize($object, $format, $context);

        // If method is get we do not need to state that
        if ($base['method'] === Request::METHOD_GET) {
            unset($base['method']);
        }

        return $base;
    }

    #[\Override]
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Link;
    }

    #[\Override]
    public function getSupportedTypes(?string $format): array
    {
        return [Link::class => true];
    }
}
