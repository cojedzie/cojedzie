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

namespace App\Service;

use App\Dto\Line;
use App\Dto\Referable;
use App\Dto\Stop;
use App\Dto\Track;
use App\Entity\LineEntity;
use App\Entity\ProviderEntity;
use App\Entity\StopEntity;
use App\Entity\TrackEntity;
use App\Exception\InvalidArgumentException;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Support\Collection;
use function Kadet\Functional\partial;
use function Kadet\Functional\ref;
use const Kadet\Functional\_;

final class EntityReferenceFactory
{
    protected array $mapping = [
        Line::class  => LineEntity::class,
        Stop::class  => StopEntity::class,
        Track::class => TrackEntity::class,
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly IdUtils $id
    ) {
    }

    public function create($object, ProviderEntity $provider)
    {
        return match (true) {
            $object instanceof Referable  => $this->createEntityReference($object, $provider),
            is_array($object)             => array_map(partial(ref([$this, 'createEntityReference']), _, $provider), $object),
            $object instanceof Collection => $object->map(partial(ref([$this, 'createEntityReference']), _, $provider)),
            default                       => throw InvalidArgumentException::invalidType(
                'object',
                $object,
                [Referable::class, Collection::class, 'array']
            ),
        };
    }

    private function createEntityReference(Referable $object, ProviderEntity $provider)
    {
        $class = $object::class;

        if (!array_key_exists($class, $this->mapping)) {
            throw new \InvalidArgumentException(sprintf("Cannot make entity reference of %s.", $class));
        }

        return $this->em->getReference(
            $this->mapping[$class],
            $this->id->generate($provider, $object->getId())
        );
    }
}
