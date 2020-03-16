<?php

namespace App\Service;

use App\Entity\TrackStopEntity;
use App\Entity\TripStopEntity;
use App\Model\ScheduledStop;
use App\Model\TrackStop;

class ScheduledStopConverter implements Converter, RecursiveConverter
{
    use RecursiveConverterTrait;

    public function convert($entity)
    {
        if ($entity instanceof TrackStopEntity) {
            return TrackStop::createFromArray([
                'stop'  => $this->parent->convert($entity->getStop()),
                'track' => $this->parent->convert($entity->getTrack()),
                'order' => $entity->getOrder(),
            ]);
        }

        if ($entity instanceof TripStopEntity) {
            return ScheduledStop::createFromArray([
                'arrival'   => $entity->getArrival(),
                'departure' => $entity->getDeparture(),
                'stop'      => $this->parent->convert($entity->getStop()),
                'order'     => $entity->getOrder(),
                'track'     => $this->parent->convert($entity->getTrip()->getTrack()),
                'trip'      => $this->parent->convert($entity->getTrip()),
            ]);
        }
    }

    public function supports($entity)
    {
        return $entity instanceof TripStopEntity
            || $entity instanceof TrackStopEntity;
    }
}
