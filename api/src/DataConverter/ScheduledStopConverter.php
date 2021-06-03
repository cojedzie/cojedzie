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

namespace App\DataConverter;

use App\Entity\TrackStopEntity;
use App\Entity\TripStopEntity;
use App\Model\DTO;
use App\Model\ScheduledStop;
use App\Model\TrackStop;

class ScheduledStopConverter implements Converter, RecursiveConverter
{
    use RecursiveConverterTrait;

    public function convert($entity, string $type)
    {
        if ($entity instanceof TrackStopEntity) {
            return TrackStop::createFromArray([
                'stop'  => $this->parent->convert($entity->getStop(), $type),
                'track' => $this->parent->convert($entity->getTrack(), $type),
                'order' => $entity->getOrder(),
            ]);
        }

        if ($entity instanceof TripStopEntity) {
            return ScheduledStop::createFromArray([
                'arrival'   => $entity->getArrival(),
                'departure' => $entity->getDeparture(),
                'stop'      => $this->parent->convert($entity->getStop(), $type),
                'order'     => $entity->getOrder(),
                'track'     => $this->parent->convert($entity->getTrip()->getTrack(), $type),
                'trip'      => $this->parent->convert($entity->getTrip(), $type),
            ]);
        }

        return null;
    }

    public function supports($entity, string $type)
    {
        return ($entity instanceof TripStopEntity || $entity instanceof TrackStopEntity)
            && ($type === DTO::class || is_subclass_of($type, DTO::class, true));
    }
}
