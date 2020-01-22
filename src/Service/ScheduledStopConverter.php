<?php

namespace App\Service;

use App\Entity\TripStopEntity;
use App\Model\ScheduledStop;

class ScheduledStopConverter implements Converter, RecursiveConverter
{
    use RecursiveConverterTrait;

    public function convert($entity)
    {
        /** @var ScheduledStop $entity */

        return ScheduledStop::createFromArray([
            'arrival'   => $entity->getArrival(),
            'departure' => $entity->getDeparture(),
            'stop'      => $this->parent->convert($entity->getStop()),
            'order'     => $entity->getOrder(),
        ]);
    }

    public function supports($entity)
    {
        return $entity instanceof TripStopEntity;
    }
}
