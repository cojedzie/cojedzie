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

use App\Dto\Departure;
use App\Dto\TimetableEntry;
use App\Dto\TimetableRange;
use App\Entity\TripEntity;
use App\Entity\TripStopEntity;
use App\Filter\Handler\Database\TimetableDayDatabaseHandler;
use App\Filter\Requirement\Requirement;
use App\Filter\Requirement\TimetableDay;
use App\Provider\TimetableRepository;
use Ds\Seq;

class GenericTimetableRepository extends DatabaseRepository implements TimetableRepository
{
    /**
     * Time zone of the provider, in which timetable days are dates.
     */
    private ?\DateTimeZone $timezone = null;

    public function withTimezone(\DateTimeZone $timezone): static
    {
        $result           = clone $this;
        $result->timezone = $timezone;

        return $result;
    }

    #[\Override]
    public function all(Requirement ...$requirements): Seq
    {
        $builder = $this->em
            ->createQueryBuilder()
            ->select('trip_stop')
            ->from(TripStopEntity::class, 'trip_stop')
            ->orderBy('trip_stop.departure', 'ASC')
        ;

        return $this->allFromQueryBuilder($builder, $requirements, $this->meta());
    }

    #[\Override]
    public function departures(Requirement ...$requirements): Seq
    {
        // the aliases are the ones the embeds use, so asking for them again joins nothing twice
        $builder = $this->em
            ->createQueryBuilder()
            ->select(
                'trip_stop',
                'trip_stop_trip',
                'trip_stop_trip_track',
                'trip_stop_trip_track_final',
                'trip_stop_trip_track_line',
            )
            ->from(TripStopEntity::class, 'trip_stop')
            ->join('trip_stop.trip', 'trip_stop_trip')
            ->join('trip_stop_trip.track', 'trip_stop_trip_track')
            ->join('trip_stop_trip_track.final', 'trip_stop_trip_track_final')
            ->join('trip_stop_trip_track.line', 'trip_stop_trip_track_line')
            ->orderBy('trip_stop.departure', 'ASC')
            ->addOrderBy('trip_stop_trip_track_line.symbol', 'ASC')
            // a line has many entries at the same time, a unique key keeps the pages from overlapping
            ->addOrderBy('trip_stop.id', 'ASC')
        ;

        return $this->allFromQueryBuilder(
            $builder,
            $requirements,
            $this->meta(),
            fn (TripStopEntity $entry) => $this->toDeparture($this->convert($entry)),
        );
    }

    #[\Override]
    public function range(): ?TimetableRange
    {
        // one lookup at each end of the (provider_id, service_date) index
        ['first' => $first, 'last' => $last] = $this->em
            ->createQueryBuilder()
            ->select('MIN(trip.serviceDate) AS first', 'MAX(trip.serviceDate) AS last')
            ->from(TripEntity::class, 'trip')
            ->where('trip.provider = :provider')
            ->setParameter('provider', $this->provider)
            ->getQuery()
            ->getSingleResult()
        ;

        // aggregates come back as the database's date strings, Y-m-d in every engine
        return $first !== null ? new TimetableRange($first, $last) : null;
    }

    #[\Override]
    protected static function getHandlers()
    {
        return array_merge(parent::getHandlers(), [
            TimetableDay::class => TimetableDayDatabaseHandler::class,
        ]);
    }

    private function meta(): array
    {
        return [
            'alias'    => 'trip_stop',
            'type'     => TimetableEntry::class,
            'entity'   => TripStopEntity::class,
            'timezone' => $this->timezone,
        ];
    }

    private function toDeparture(TimetableEntry $entry): Departure
    {
        $track = $entry->getTrack();

        return Departure::createFromArray([
            // a trip may visit a stop twice, its place in the trip tells the visits apart
            'key'        => sprintf(
                '%s::%s::%d',
                $entry->getTrip()->getId(),
                $entry->getStop()->getId(),
                $entry->getOrder(),
            ),
            'line'       => $track->getLine(),
            'stop'       => $entry->getStop(),
            'track'      => $track,
            'trip'       => $entry->getTrip(),
            'display'    => $track->getDestination()->getName(),
            'scheduled'  => $entry->getDeparture(),
            'serviceDay' => $entry->getTrip()->getServiceDay(),
        ]);
    }
}
