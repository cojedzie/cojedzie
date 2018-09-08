<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait ProviderReferenceTrait
{
    /**
     * @ORM\ManyToOne(targetEntity=ProviderEntity::class, cascade={"persist", "remove"})
     *
     * @var ProviderEntity
     */
    private $provider;

    /**
     * @return ProviderEntity
     */
    public function getProvider(): ProviderEntity
    {
        return $this->provider;
    }

    /**
     * @param ProviderEntity|null $provider
     */
    public function setProvider(ProviderEntity $provider): void
    {
        $this->provider = $provider;
    }
}