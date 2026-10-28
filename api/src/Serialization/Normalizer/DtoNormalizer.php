<?php

namespace App\Serialization\Normalizer;

use App\Event\PostNormalizationEvent;
use Ds\Seq;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DtoNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    public function __construct(
        private readonly NormalizerInterface&DenormalizerInterface $decorated,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    #[\Override]
    public function normalize($object, ?string $format = null, array $context = []): array|\ArrayObject
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
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $this->decorated->supportsNormalization($data, $format, $context);
    }

    #[\Override]
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $context[AbstractNormalizer::CALLBACKS] = [
            ...$this->getSeqCallbacks($type),
            ...$context[AbstractNormalizer::CALLBACKS] ?? [],
        ];

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
    public function setSerializer(SerializerInterface $serializer): void
    {
        if ($this->decorated instanceof SerializerAwareInterface) {
            $this->decorated->setSerializer($serializer);
        }
    }

    /**
     * Symfony denormalizes a `Seq<T>` property into a `T[]` array, so the array has to be wrapped into a Seq.
     *
     * Callbacks are passed on to nested objects, which may have a property with the same name and a different type,
     * so the callback checks the type of the property it is applied to.
     *
     * @return array<string, callable>
     */
    private function getSeqCallbacks(string $class): array
    {
        if (!class_exists($class)) {
            return [];
        }

        $callbacks = [];
        foreach ((new \ReflectionClass($class))->getProperties() as $property) {
            if (self::isSeqProperty($class, $property->getName())) {
                $callbacks[$property->getName()] = self::wrapInSeq(...);
            }
        }

        return $callbacks;
    }

    private static function wrapInSeq(mixed $value, object|string $object, string $attribute): mixed
    {
        return is_array($value) && self::isSeqProperty($object, $attribute) ? new Seq($value) : $value;
    }

    private static function isSeqProperty(object|string $class, string $property): bool
    {
        if (!property_exists($class, $property)) {
            return false;
        }

        $type = (new \ReflectionProperty($class, $property))->getType();

        return $type instanceof \ReflectionNamedType && $type->getName() === Seq::class;
    }

    private function isEmpty(mixed $value)
    {
        if (is_countable($value)) {
            return count($value) == 0;
        }

        return empty($value);
    }
}
