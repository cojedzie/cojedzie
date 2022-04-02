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

use App\Entity\StopEntity;
use App\Entity\TrackEntity;
use App\Entity\TripStopEntity;
use App\Filter\Requirement\Requirement;
use App\Model\Departure;
use App\Model\ScheduledStop;
use App\Model\Stop;
use App\Provider\ScheduleRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GenericScheduleRepository extends DatabaseRepository implements ScheduleRepository
{
    public function getDeparturesForStop(
        Stop $stop,
        Carbon $from,
        int $count = ScheduleRepository::DEFAULT_DEPARTURES_COUNT
    ): Collection {
        $query = $this->em
            ->createQueryBuilder()
            ->select('ts', 't')
            ->from(TripStopEntity::class, 'ts')
            ->join('ts.trip', 't')
            ->where('ts.departure >= :from')
            ->andWhere('ts.stop = :stop')
            ->orderBy('ts.departure', 'ASC')
            ->setMaxResults($count)
            ->getQuery();

        $schedule = collect($query->execute([
            'from' => $from,
            'stop' => $this->reference(StopEntity::class, $stop),
        ]));

        $this->em->createQueryBuilder()
            ->select('t', 's', 'st')
            ->from(TrackEntity::class, 't')
            ->join('t.stopsInTrack', 's')
            ->join('s.stop', 'st')
            ->where('t.id in (:tracks)')
            ->getQuery()
            ->execute([
                ':tracks' => $schedule->map(fn (TripStopEntity $stop) => $stop->getTrip()->getTrack()->getId())->all(),
            ]);

        return $schedule->map(function (TripStopEntity $entity) use ($stop) {
            $trip = $entity->getTrip();
            $track = $trip->getTrack();
            $line = $track->getLine();
            /** @var StopEntity $last */
            $last = $entity->getTrip()->getTrack()->getStopsInTrack()->last()->getStop();

            return Departure::createFromArray([
                'key'       => sprintf('%s::%s', $this->id->of($entity->getTrip()), $entity->getDeparture()->format('H:i')),
                'scheduled' => $entity->getDeparture(),
                'stop'      => $stop,
                'display'   => $last->getName(),
                'line'      => $this->convert($line),
                'track'     => $this->convert($track),
                'trip'      => $this->convert($trip),
            ]);
        });
    }

    public function all(Requirement ...$requirements): Collection
    {
        $builder = $this->em
            ->createQueryBuilder()
            ->select('trip_stop')
            ->from(TripStopEntity::class, 'trip_stop')
            ->orderBy('trip_stop.departure', 'ASC')
        ;

        return $this->allFromQueryBuilder($builder, $requirements, [
            'alias'  => 'trip_stop',
            'type'   => ScheduledStop::class,
            'entity' => TripStopEntity::class,
        ]);
    }
}
