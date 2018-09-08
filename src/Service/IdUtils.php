<?php

namespace App\Service;

use App\Entity\Entity;
use App\Entity\ProviderEntity;

class IdUtils
{
    const DELIMITER = '::';

    public function generate(ProviderEntity $provider, $id)
    {
        return sprintf('%s%s%s', $provider->getId(), self::DELIMITER, $id);
    }

    public function strip($id)
    {
        return explode(self::DELIMITER, $id)[1];
    }

    public function of(Entity $entity)
    {
        return $this->strip($entity->getId());
    }
}