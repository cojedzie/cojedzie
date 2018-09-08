<?php

namespace App\Service;

use App\Entity\Entity;
use App\Entity\ProviderEntity;

class IdUtils
{
    public function generate(ProviderEntity $provider, $id)
    {
        return sprintf('%s-%s', $provider->getId(), $id);
    }

    public function strip(ProviderEntity $provider, $id)
    {
        return substr($id, strlen($provider->getId()) + 1);
    }

    public function of(Entity $entity)
    {
        return $this->strip($entity->getProvider(), $entity->getId());
    }
}