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

namespace App\Handler\Database;

use App\DataConverter\CacheableConverter;
use App\DataConverter\Converter;
use App\Entity\TrackEntity;
use App\Event\PostProcessEvent;
use App\Handler\PostProcessingHandler;
use App\Model\Destination;
use App\Model\DTO;
use App\Model\Stop;
use App\Service\IdUtils;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Support\Collection;
use Kadet\Functional as f;
use Kadet\Functional\Transforms as t;

class WithDestinationsDatabaseHandler implements PostProcessingHandler
{
    private $em;
    private $converter;
    private $id;

    public function __construct(EntityManagerInterface $entityManager, Converter $converter, IdUtils $id)
    {
        $this->em = $entityManager;
        $this->converter = $converter;
        $this->id = $id;

        if ($this->converter instanceof CacheableConverter) {
            $this->converter = clone $this->converter;
            $this->converter->reset();
        }
    }

    public function postProcess(PostProcessEvent $event)
    {
        $provider = $event->getMeta()['provider'];
        $stops = $event
            ->getData()
            ->map(t\property('id'))
            ->map(f\apply([$this->id, 'generate'], $provider))
            ->all();

        $destinations = collect($this->em->createQueryBuilder()
            ->select('t', 'tl', 'f', 'fs', 'ts')
            ->from(TrackEntity::class, 't')
            ->join('t.stopsInTrack', 'ts')
            ->join('t.line', 'tl')
            ->where('ts.stop IN (:stops)')
            ->join('t.final', 'f')
            ->join('f.stop', 'fs')
            ->getQuery()
            ->execute(['stops' => $stops]))
            ->reduce(function ($grouped, TrackEntity $track) {
                foreach ($track->getStopsInTrack()->map(t\property('stop'))->map(t\property('id')) as $stop) {
                    $grouped[$stop] = ($grouped[$stop] ?? collect())->add($track);
                }

                return $grouped;
            }, collect())
            ->map(function (Collection $tracks) {
                return $tracks
                    ->groupBy(function (TrackEntity $track) {
                        return $track->getFinal()->getStop()->getId();
                    })->map(function (Collection $tracks, $id) {
                        return Destination::createFromArray([
                            'stop'  => $this->converter->convert($tracks->first()->getFinal()->getStop(), DTO::class),
                            'lines' => $tracks
                                ->map(t\property('line'))
                                ->unique(t\property('id'))
                                ->map(f\partial(f\ref([$this->converter, 'convert']), f\_, DTO::class))
                                ->values(),
                        ]);
                    })->values();
            });

        $event->getData()->each(function (Stop $stop) use ($provider, $destinations) {
            $stop->setDestinations($destinations[$this->id->generate($provider, $stop->getId())]);
        });
    }
}
