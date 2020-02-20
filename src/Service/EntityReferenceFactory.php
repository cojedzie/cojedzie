<?php

namespace App\Service;

use App\Entity\LineEntity;
use App\Entity\ProviderEntity;
use App\Entity\StopEntity;
use App\Entity\TrackEntity;
use App\Model\Line;
use App\Model\Referable;
use App\Model\Stop;
use App\Model\Track;
use Doctrine\ORM\EntityManagerInterface;

final class EntityReferenceFactory
{
    protected $mapping = [
        Line::class  => LineEntity::class,
        Stop::class  => StopEntity::class,
        Track::class => TrackEntity::class,
    ];

    private $em;
    private $id;

    public function __construct(EntityManagerInterface $em, IdUtils $id)
    {
        $this->em = $em;
        $this->id = $id;
    }

    public function create(Referable $object, ProviderEntity $provider)
    {
        $class = get_class($object);

        if (!array_key_exists($class, $this->mapping)) {
            throw new \InvalidArgumentException(sprintf("Cannot make entity reference of %s.", $class));
        }

        return $this->em->getReference(
            $this->mapping[$class],
            $this->id->generate($provider, $object->getId())
        );
    }
}
