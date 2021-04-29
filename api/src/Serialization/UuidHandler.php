<?php

declare(strict_types=1);

namespace App\Serialization;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Class LaravelCollectionHandler
 *
 * Shamelessly copied from https://github.com/schmittjoh/serializer/blob/master/src/Handler/ArrayCollectionHandler.php
 *
 * @package App\Serialization
 */
final class UuidHandler implements SubscribingHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribingMethods()
    {
        $methods = [];
        $formats = ['json', 'xml', 'yml'];

        $collectionTypes = [
            'uuid',
            Uuid::class,
        ];

        foreach ($collectionTypes as $type) {
            foreach ($formats as $format) {
                $methods[] = [
                    'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
                    'type'      => $type,
                    'format'    => $format,
                    'method'    => 'serialize',
                ];

                $methods[] = [
                    'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                    'type'      => $type,
                    'format'    => $format,
                    'method'    => 'deserialize',
                ];
            }
        }

        return $methods;
    }

    /**
     * @return string
     */
    public function serialize(
        SerializationVisitorInterface $visitor,
        Uuid $uuid,
        array $type,
        SerializationContext $context
    ) {
        return $uuid->toRfc4122();
    }

    /**
     * @param string $data
     */
    public function deserialize(
        DeserializationVisitorInterface $visitor,
        $data,
        array $type,
        DeserializationContext $context
    ): Uuid {
        return Uuid::fromString($data);
    }
}
