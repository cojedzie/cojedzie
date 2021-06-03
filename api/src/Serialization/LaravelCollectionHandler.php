<?php
/*
 * Copyright (C) 2021 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace App\Serialization;

use Illuminate\Support\Collection;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;
use JMS\Serializer\Visitor\SerializationVisitorInterface;

/**
 * Class LaravelCollectionHandler
 *
 * Shamelessly copied from https://github.com/schmittjoh/serializer/blob/master/src/Handler/ArrayCollectionHandler.php
 *
 * @package App\Serialization
 */
final class LaravelCollectionHandler implements SubscribingHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribingMethods()
    {
        $methods = [];
        $formats = ['json', 'xml', 'yml'];
        $collectionTypes = [
            'Collection',
            Collection::class,
        ];

        foreach ($collectionTypes as $type) {
            foreach ($formats as $format) {
                $methods[] = [
                    'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
                    'type'      => $type,
                    'format'    => $format,
                    'method'    => 'serializeCollection',
                ];

                $methods[] = [
                    'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                    'type'      => $type,
                    'format'    => $format,
                    'method'    => 'deserializeCollection',
                ];
            }
        }

        return $methods;
    }

    /**
     * @return array|\ArrayObject
     */
    public function serializeCollection(
        SerializationVisitorInterface $visitor,
        Collection $collection,
        array $type,
        SerializationContext $context
    ) {
        // We change the base type, and pass through possible parameters.
        $type['name'] = 'array';
        $result = $visitor->visitArray($collection->all(), $type);

        return $result;
    }

    /**
     * @param mixed $data
     */
    public function deserializeCollection(
        DeserializationVisitorInterface $visitor,
        $data,
        array $type,
        DeserializationContext $context
    ): Collection {
        // See above.
        $type['name'] = 'array';

        return new Collection($visitor->visitArray($data, $type));
    }
}
