<?php

namespace App\Service\Normalizer;

use App\Model\Stop;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Tightenco\Collect\Support\Arr;

class StopNormalizer implements NormalizerInterface
{
    private $normalizer;

    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function normalize($object, $format = null, array $context = [])
    {
        return Arr::except($this->normalizer->normalize($object), ['latitude', 'longitude']);
    }

    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Stop;
    }
}