<?php

namespace App\Service;

use App\Entity\LineEntity;
use App\Entity\ProviderEntity;
use App\Entity\StopEntity;
use App\Entity\TrackEntity;
use App\Exception\InvalidArgumentException;
use App\Model\Line;
use App\Model\Referable;
use App\Model\Stop;
use App\Model\Track;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Support\Collection;
use function Kadet\Functional\partial;
use function Kadet\Functional\ref;
use const Kadet\Functional\_;

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

    public function create($object, ProviderEntity $provider)
    {
        switch (true) {
            case $object instanceof Referable:
                return $this->createEntityReference($object, $provider);
            case is_array($object):
                return array_map(partial(ref([$this, 'createEntityReference']), _, $provider), $object);
            case $object instanceof Collection:
                return $object->map(partial(ref([$this, 'createEntityReference']), _, $provider));
            default:
                throw InvalidArgumentException::invalidType(
                    'object',
                    $object,
                    [Referable::class, Collection::class, 'array']
                );
        }
    }

    private function createEntityReference(Referable $object, ProviderEntity $provider)
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
