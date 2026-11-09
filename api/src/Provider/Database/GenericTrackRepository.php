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
use App\Filter\Handler\Database\TrackKindDatabaseHandler;
use App\Filter\Handler\Database\ValidityDatabaseHandler;
use App\Filter\Requirement\Requirement;
use App\Filter\Requirement\TrackKindConstraint;
use App\Filter\Requirement\ValidityConstraint;
use App\Provider\TrackRepository;
use App\Service\HandlerProviderFactory;
use App\Service\IdUtils;
use Doctrine\ORM\EntityManagerInterface;
use Ds\Seq;
use Psr\Clock\ClockInterface;

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
            ->select(['track_stop']);

        return $this->allFromQueryBuilder($builder, $this->riderFacingByDefault($requirements), [
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
            ->select('track');

        return $this->allFromQueryBuilder($builder, $this->riderFacingByDefault($requirements), [
            'alias'  => 'track',
            'entity' => TrackEntity::class,
            'type'   => Track::class,
        ]);
    }

    #[\Override]
    protected static function getHandlers()
    {
        return array_merge(parent::getHandlers(), [
            ValidityConstraint::class  => ValidityDatabaseHandler::class,
            TrackKindConstraint::class => TrackKindDatabaseHandler::class,
        ]);
    }

    /**
     * Unless asked otherwise, lists what riders can take now: the other versions of a route are past or future ones,
     * and depot runs aren't for riders (ADR 0031).
     *
     * @param Requirement[] $requirements
     *
     * @return Requirement[]
     */
    private function riderFacingByDefault(array $requirements): array
    {
        return self::withDefaults(
            $requirements,
            new ValidityConstraint($this->clock->now()),
            TrackKindConstraint::passenger(),
        );
    }
}
