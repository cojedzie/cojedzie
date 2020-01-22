<?php

namespace App\Provider\Database;

use App\Entity\Entity;
use App\Entity\ProviderEntity;
use App\Model\Referable;
use App\Service\Converter;
use App\Service\IdUtils;
use Doctrine\ORM\EntityManagerInterface;
use Kadet\Functional as f;

class DatabaseRepository
{
    /** @var EntityManagerInterface */
    protected $em;

    /** @var ProviderEntity */
    protected $provider;

    /** @var IdUtils */
    protected $id;

    /** @var Converter */
    protected $converter;

    /**
     * DatabaseRepository constructor.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em, IdUtils $id, Converter $converter)
    {
        $this->em        = $em;
        $this->id        = $id;
        $this->converter = $converter;
    }

    /** @return static */
    public function withProvider(ProviderEntity $provider)
    {
        $result = clone $this;
        $result->provider = $provider;

        return $result;
    }

    protected function convert($entity)
    {
        return $this->converter->convert($entity);
    }

    protected function reference($class, Referable $referable)
    {
        $id = $this->id->generate($this->provider, $referable->getId());

        return $this->em->getReference($class, $id);
    }
}
