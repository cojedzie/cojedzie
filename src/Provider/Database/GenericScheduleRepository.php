<?php

namespace App\Provider\Database;

use App\Entity\StopEntity;
use App\Entity\StopInTrack;
use App\Entity\TrackEntity;
use App\Entity\TripStopEntity;
use App\Model\Departure;
use App\Model\Line;
use App\Model\Stop;
use App\Model\Vehicle;
use App\Provider\ScheduleRepository;
use Carbon\Carbon;
use Tightenco\Collect\Support\Collection;
use function Kadet\Functional\ref;

class GenericScheduleRepository extends DatabaseRepository implements ScheduleRepository
{
    public function getDeparturesForStop(
        Stop $stop,
        \DateTime $from,
        int $count = ScheduleRepository::DEFAULT_DEPARTURES_COUNT
    ): Collection {
        $query = $this->em
            ->createQueryBuilder()
            ->select('ts', 't')
            ->from(TripStopEntity::class, 'ts')
            ->where('ts.departure >= :from')
            ->andWhere('ts.stop = :stop')
            ->join('ts.trip', 't')
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
            ->orderBy('s.order', 'DESC')
            ->getQuery()
            ->execute([
                ':tracks' => $schedule->map(function (TripStopEntity $stop) {
                    return $stop->getTrip()->getTrack()->getId();
                })->all()
            ]);

        return $schedule->map(function (TripStopEntity $entity) use ($stop) {
            $line = $entity->getTrip()->getTrack()->getLine();
            /** @var StopEntity $last */
            $last = $entity->getTrip()->getTrack()->getStopsInTrack()->last()->getStop();

            return Departure::createFromArray([
                'scheduled' => $entity->getDeparture(),
                'stop'      => $stop,
                'display'   => $last->getName(),
                'line'      => $this->convert($line),
            ]);
        });
    }
}
