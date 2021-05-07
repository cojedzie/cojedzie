<?php

namespace App\DataConverter;

use App\Model\DTO;
use App\Model\Provider as ProviderDTO;
use App\Provider\Provider;

class ProviderConverter implements Converter
{
    public function convert($entity, string $type)
    {
        /** @var Provider $entity */

        return ProviderDTO::createFromArray([
            'id'          => $entity->getIdentifier(),
            'shortName'   => $entity->getShortName(),
            'name'        => $entity->getName(),
            'attribution' => $entity->getAttribution(),
            'lastUpdate'  => $entity->getLastUpdate() ? clone $entity->getLastUpdate() : null,
            'location'    => $entity->getLocation(),
        ]);
    }

    public function supports($entity, string $type)
    {
        return $entity instanceof Provider
            && ($type === DTO::class || is_subclass_of($type, DTO::class, true));
    }
}

