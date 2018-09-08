<?php

namespace App\Provider\Database;

use App\Entity\ProviderEntity;
use App\Service\IdUtils;
use Doctrine\ORM\EntityManagerInterface;

class DatabaseRepository
{
    /** @var EntityManagerInterface */
    protected $em;

    /** @var ProviderEntity */
    protected $provider;

    /** @var IdUtils */
    protected $id;

    /**
     * DatabaseRepository constructor.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em, IdUtils $id)
    {
        $this->em = $em;
        $this->id = $id;
    }

    /** @return static */
    public function withProvider(ProviderEntity $provider)
    {
        $result = clone $this;
        $result->provider = $provider;

        return $result;
    }
}