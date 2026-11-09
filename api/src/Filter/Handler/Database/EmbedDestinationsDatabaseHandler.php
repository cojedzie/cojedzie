<?php
/*
 * Copyright (C) 2022 Kacper Donat
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

namespace App\Filter\Handler\Database;

use App\DataConverter\CacheableConverter;
use App\DataConverter\Converter;
use App\Dto\Destination;
use App\Dto\Dto;
use App\Dto\Stop;
use App\Entity\LineEntity;
use App\Entity\TrackEntity;
use App\Entity\TrackStopEntity;
use App\Event\HandleDatabaseRequirementEvent;
use App\Event\PostProcessEvent;
use App\Filter\Handler\PostProcessingHandler;
use App\Filter\Requirement\TrackKindConstraint;
use App\Filter\Requirement\ValidityConstraint;
use App\Service\IdUtils;
use Doctrine\ORM\EntityManagerInterface;
use Ds\Seq;
use Kadet\Functional\Collections as c;
use Kadet\Functional\Transforms as t;
use Psr\Clock\ClockInterface;

class EmbedDestinationsDatabaseHandler implements PostProcessingHandler
{
    /**
     * @noRector Rector\Php81\Rector\Property\ReadOnlyPropertyRector
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
        private Converter $converter,
        private readonly IdUtils $id,
        private readonly ClockInterface $clock,
        private readonly ValidityDatabaseHandler $validity,
        private readonly TrackKindDatabaseHandler $kind,
    ) {
        if ($this->converter instanceof CacheableConverter) {
            $this->converter = clone $this->converter;
            $this->converter->reset();
        }
    }

    #[\Override]
    public function postProcess(PostProcessEvent $event)
    {
        $provider = $event->getMeta()['provider'];
        $stops    = $event->getData()
            |> c\map(fn (Stop $stop) => $this->id->generate($provider, $stop->getId()))
            |> c\toArray();

        $builder = $this->em->createQueryBuilder()
            ->select('t', 'tl', 'f', 'fs', 'ts')
            ->from(TrackEntity::class, 't')
            ->join('t.stopsInTrack', 'ts')
            ->join('t.line', 'tl')
            ->where('ts.stop IN (:stops)')
            ->join('t.final', 'f')
            ->join('f.stop', 'fs')
            ->setParameter('stops', $stops);

        // destinations of the tracks valid now, other versions of a route are past or future ones
        $this->validity->process(new HandleDatabaseRequirementEvent(
            new ValidityConstraint($this->clock->now()),
            $event->getRepository(),
            $builder,
            ['alias' => 't'],
        ));

        // depot runs aren't for riders (ADR 0031)
        $this->kind->process(new HandleDatabaseRequirementEvent(
            TrackKindConstraint::passenger(),
            $event->getRepository(),
            $builder,
            ['alias' => 't'],
        ));

        $tracks = $builder->getQuery()->execute();

        $destinations = $tracks
            |> c\flatMap(fn (TrackEntity $track) => $track->getStopsInTrack()
                |> c\map(fn (TrackStopEntity $stop) => [
                    'stop'  => $stop->getStop()->getId(),
                    'track' => $track,
                ]))
            |> c\groupBy(fn (array $entry) => $entry['stop'])
            |> c\map(fn (Seq $entries) => $entries
                |> c\map(fn (array $entry) => $entry['track'])
                |> c\groupBy(fn (TrackEntity $track) => $track->getFinal()->getStop()->getId())
                |> c\map(fn (Seq $tracks) => Destination::createFromArray([
                    'stop'  => $this->converter->convert(($tracks |> c\first())->getFinal()->getStop(), Dto::class),
                    'lines' => $tracks
                        |> c\map(t\property('line'))
                        |> c\uniqueBy(t\property('id'))
                        |> c\map(fn (LineEntity $line) => $this->converter->convert($line, Dto::class)),
                ]))
                |> c\values());

        foreach ($event->getData() as $stop) {
            $stop->setDestinations($destinations->get($this->id->generate($provider, $stop->getId()), new Seq()));
        }
    }
}
