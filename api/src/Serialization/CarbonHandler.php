<?php

declare(strict_types=1);

namespace App\Serialization;

use Carbon\Carbon;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\DateHandler;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Tightenco\Collect\Support\Collection;

/**
 * Class LaravelCollectionHandler
 *
 * Shamelessly copied from https://github.com/schmittjoh/serializer/blob/master/src/Handler/ArrayCollectionHandler.php
 *
 * @package App\Serialization
 */
final class CarbonHandler implements SubscribingHandlerInterface
{
    private $dateTimeHandler;

    public function __construct(DateHandler $dateHandler)
    {
        $this->dateTimeHandler = $dateHandler;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribingMethods()
    {
        $methods = [];
        $formats = ['json', 'xml', 'yml'];

        $collectionTypes = [
            'Carbon',
            Carbon::class,
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
     * @return array|\ArrayObject
     */
    public function serialize(
        SerializationVisitorInterface $visitor,
        Carbon $date,
        array $type,
        SerializationContext $context
    ) {
        return $this->dateTimeHandler->serializeDateTime($visitor, $date->tz('Europe/Warsaw'), $type, $context);
    }

    /**
     * @param mixed $data
     */
    public function deserialize(
        DeserializationVisitorInterface $visitor,
        $data,
        array $type,
        DeserializationContext $context
    ): Collection {
        return new Collection($visitor->visitArray($data, $type));
    }
}
