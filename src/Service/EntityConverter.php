<?php

namespace App\Service;

use App\Entity\Entity;
use App\Entity\LineEntity;
use App\Entity\OperatorEntity;
use App\Entity\StopEntity;
use App\Entity\TrackEntity;
use App\Model\Line;
use App\Model\Operator;
use App\Model\Stop;
use App\Model\Track;
use App\Service\Proxy\ReferenceObjectFactory;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Proxy\Proxy;
use Kadet\Functional as f;
use const Kadet\Functional\_;

final class EntityConverter
{
    private $id;
    private $reference;

    public function __construct(IdUtils $id, ReferenceObjectFactory $reference)
    {
        $this->id        = $id;
        $this->reference = $reference;
    }

    /**
     * @param Entity $entity
     * @param array  $cache
     *
     * @return Line|Track|Stop|Operator
     */
    public function convert(Entity $entity, array $cache = [])
    {
        if (array_key_exists($key = get_class($entity).':'.$this->getId($entity), $cache)) {
            return $cache[$key];
        }

        if ($entity instanceof Proxy && !$entity->__isInitialized()) {
            return $this->reference($entity);
        }

        $result  = $this->create($entity);
        $cache   = $cache + [$key => $result];
        $convert = f\partial([$this, 'convert'], _, $cache);

        switch (true) {
            case $entity instanceof OperatorEntity:
                $result->fill([
                    'name'  => $entity->getName(),
                    'phone' => $entity->getPhone(),
                    'email' => $entity->getEmail(),
                    'url'   => $entity->getEmail(),
                ]);
                break;

            case $entity instanceof LineEntity:
                $result->fill([
                    'id'       => $this->id->of($entity),
                    'symbol'   => $entity->getSymbol(),
                    'type'     => $entity->getType(),
                    'operator' => $convert($entity->getOperator()),
                    'night'    => $entity->isNight(),
                    'fast'     => $entity->isFast(),
                    'tracks'   => $this->collection($entity->getTracks(), $convert),
                ]);
                break;

            case $entity instanceof TrackEntity:
                $result->fill([
                    'variant'     => $entity->getVariant(),
                    'description' => $entity->getDescription(),
                    'stops'       => $this->collection($entity->getStops(), $convert),
                    'line'        => $convert($entity->getLine()),
                ]);
                break;

            case $entity instanceof StopEntity:
                $result->fill([
                    'name'        => $entity->getName(),
                    'variant'     => $entity->getVariant(),
                    'description' => $entity->getDescription(),
                    'location'    => [
                        $entity->getLatitude(),
                        $entity->getLongitude(),
                    ],
                ]);
        }

        return $result;
    }

    // HACK to not trigger doctrine stupid lazy loading.
    private function getId(Entity $entity) {
        if ($entity instanceof Proxy) {
            $id = (new \ReflectionClass(get_parent_class($entity)))->getProperty('id');
            $id->setAccessible(true);

            return $id->getValue($entity);
        }

        return $entity->getId();
    }

    private function collection(PersistentCollection $collection, $converter)
    {
        if ($collection->isInitialized()) {
            return collect($collection)->map($converter);
        }

        return collect();
    }

    private function getModelClassForEntity(Entity $entity)
    {
        switch (true) {
            case $entity instanceof OperatorEntity:
                return Operator::class;

            case $entity instanceof LineEntity:
                return Line::class;

            case $entity instanceof TrackEntity:
                return Track::class;

            case $entity instanceof StopEntity:
                return Stop::class;

            default:
                return false;
        }
    }

    private function create(Entity $entity)
    {
        $id = $this->id->of($entity);
        $class = $this->getModelClassForEntity($entity);

        return $class::createFromArray(['id' => $id]);
    }

    private function reference(Entity $entity)
    {
        $id = $this->id->strip($this->getId($entity));
        $class = $this->getModelClassForEntity($entity);

        return $this->reference->get($class, ['id' => $id]);
    }
}