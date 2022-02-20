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

use Carbon\Carbon;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\DateHandler;
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
final class CarbonHandler implements SubscribingHandlerInterface
{
    public function __construct(private readonly DateHandler $dateTimeHandler)
    {
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

    public function serialize(
        SerializationVisitorInterface $visitor,
        Carbon $date,
        array $type,
        SerializationContext $context
    ): string {
        return $this->dateTimeHandler->serializeDateTime($visitor, $date->tz('Europe/Warsaw'), $type, $context);
    }

    public function deserialize(
        DeserializationVisitorInterface $visitor,
        string $data,
        array $type,
        DeserializationContext $context
    ): Carbon {
        return new Carbon($data);
    }
}
