<?php

namespace App\Service;

use App\Model\Provider as ProviderDTO;
use App\Provider\Provider;

class ProviderConverter implements Converter
{
    public function convert($entity)
    {
        /** @var Provider $entity */

        return ProviderDTO::createFromArray([
            'id'          => $entity->getIdentifier(),
            'shortName'   => $entity->getShortName(),
            'name'        => $entity->getName(),
            'attribution' => $entity->getAttribution(),
            'lastUpdate'  => $entity->getLastUpdate() ? clone $entity->getLastUpdate() : null,
        ]);
    }

    public function supports($entity)
    {
        return $entity instanceof Provider;
    }
}
