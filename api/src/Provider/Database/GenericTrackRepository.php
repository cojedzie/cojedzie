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

namespace App\Provider\Database;

use App\DataConverter\Converter;
use App\Dto\Track;
use App\Dto\TrackStop;
use App\Entity\TrackEntity;
use App\Entity\TrackStopEntity;
use App\Filter\Requirement\Requirement;
use App\Provider\TrackRepository;
use App\Service\HandlerProviderFactory;
use App\Service\IdUtils;
use Doctrine\ORM\EntityManagerInterface;
use Ds\Seq;
use Psr\Clock\ClockInterface;

/**
 * Lists only the tracks valid now: with several versions of a route imported, the others are past or future ones.
 */
class GenericTrackRepository extends DatabaseRepository implements TrackRepository
{
    public function __construct(
        EntityManagerInterface $em,
        IdUtils $id,
        Converter $converter,
        HandlerProviderFactory $handlerProviderFactory,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct($em, $id, $converter, $handlerProviderFactory);
    }

    #[\Override]
    public function stops(Requirement ...$requirements): Seq
    {
        $builder = $this->em
            ->createQueryBuilder()
            ->from(TrackStopEntity::class, 'track_stop')
            ->select(['track_stop'])
            // track stops share the validity of their track
            ->where('(track_stop.validFrom IS NULL OR track_stop.validFrom <= :now)')
            ->andWhere('(track_stop.validTo IS NULL OR track_stop.validTo >= :now)')
            ->setParameter('now', $this->clock->now(), 'datetime');

        return $this->allFromQueryBuilder($builder, $requirements, [
            'alias'  => 'track_stop',
            'entity' => TrackStopEntity::class,
            'type'   => TrackStop::class,
        ]);
    }

    #[\Override]
    public function all(Requirement ...$requirements): Seq
    {
        $builder = $this->em
            ->createQueryBuilder()
            ->from(TrackEntity::class, 'track')
            ->select('track')
            ->where('(track.validFrom IS NULL OR track.validFrom <= :now)')
            ->andWhere('(track.validTo IS NULL OR track.validTo >= :now)')
            ->setParameter('now', $this->clock->now(), 'datetime');

        return $this->allFromQueryBuilder($builder, $requirements, [
            'alias'  => 'track',
            'entity' => TrackEntity::class,
            'type'   => Track::class,
        ]);
    }
}
