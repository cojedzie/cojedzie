<?php

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
