<?php
/*
 * Copyright (C) 2021 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\DataConverter;

use App\Entity\{Entity, LineEntity, OperatorEntity, StopEntity, TrackEntity, TripEntity};
use App\Dto\{Dto, Line, Location, Operator, ScheduledStop, Stop, Track, Trip};
use App\Service\IdUtils;
use App\Service\Proxy\ReferenceFactory;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Proxy\Proxy;
use Kadet\Functional\Transforms as t;
use function collect;

final class EntityConverter implements Converter, RecursiveConverter, CacheableConverter
{
    use RecursiveConverterTrait;
    private $cache;

    public function __construct(
        private readonly IdUtils $id,
        private readonly ReferenceFactory $reference
    ) {
        $this->cache = [];
    }

    /**
     * @param Entity $entity
     */
    public function convert($entity, string $type)
    {
        if (array_key_exists($key = $entity::class . ':' . $this->getId($entity), $this->cache)) {
            return $this->cache[$key];
        }

        if ($entity instanceof Proxy && !$entity->__isInitialized()) {
            return $this->reference($entity);
        }

        $result            = $this->create($entity);
        $this->cache[$key] = $result;

        $convert = fn ($entity) => $this->supports($entity, $type)
            ? $this->convert($entity, $type)
            : $this->parent->convert($entity, $type);

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
                    'tracks'   => $this->collection($entity->getTracks())->map($convert),
                ]);
                break;

            case $entity instanceof TrackEntity:
                $result->fill([
                    'variant'     => $entity->getVariant(),
                    'description' => $entity->getDescription(),
                    'stops'       => $this->collection($entity->getStopsInTrack())
                        ->map(t\property('stop'))
                        ->map($convert),
                    'line'        => $convert($entity->getLine()),
                    'destination' => $convert($entity->getFinal()->getStop()),
                ]);
                break;

            case $entity instanceof StopEntity:
                $result->fill([
                    'name'        => $entity->getName(),
                    'variant'     => $entity->getVariant(),
                    'description' => $entity->getDescription(),
                    'group'       => $entity->getGroup(),
                    'location'    => new Location(
                        $entity->getLongitude(),
                        $entity->getLatitude()
                    ),
                ]);
                break;

            case $entity instanceof TripEntity:
                $result->fill([
                    'variant'  => $entity->getVariant(),
                    'note'     => $entity->getNote(),
                    'schedule' => $this->collection($entity->getStops())->map($convert),
                    'track'    => $convert($entity->getTrack()),
                ]);
                break;
        }

        return $result;
    }

    // HACK to not trigger doctrine stupid lazy loading.
    private function getId(Entity $entity)
    {
        if ($entity instanceof Proxy) {
            $id = (new \ReflectionClass(get_parent_class($entity)))->getProperty('id');
            $id->setAccessible(true);

            return $id->getValue($entity);
        }

        return $entity->getId();
    }

    private function collection($collection)
    {
        if (!$collection instanceof PersistentCollection || $collection->isInitialized()) {
            return collect($collection);
        }

        return collect();
    }

    private function getModelClassForEntity(Entity $entity)
    {
        return match (true) {
            $entity instanceof OperatorEntity => Operator::class,
            $entity instanceof LineEntity     => Line::class,
            $entity instanceof TrackEntity    => Track::class,
            $entity instanceof StopEntity     => Stop::class,
            $entity instanceof TripEntity     => Trip::class,
            default                           => false,
        };
    }

    private function create(Entity $entity)
    {
        $id    = $this->id->of($entity);
        $class = $this->getModelClassForEntity($entity);

        return $class::createFromArray(['id' => $id]);
    }

    private function reference(Entity $entity)
    {
        $id    = $this->id->strip($this->getId($entity));
        $class = $this->getModelClassForEntity($entity);

        return $this->reference->get($class, ['id' => $id]);
    }

    public function supports($entity, string $type)
    {
        return $entity instanceof Entity
            && ($type === Dto::class || is_subclass_of($type, Dto::class, true));
    }

    public function reset()
    {
        $this->cache = [];
    }
}
