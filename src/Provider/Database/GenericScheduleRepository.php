<?php

namespace App\Provider\Database;

use App\Entity\StopEntity;
use App\Entity\TripStopEntity;
use App\Model\Stop;
use function Kadet\Functional\ref;

class GenericScheduleRepository extends DatabaseRepository
{
    const DEFAULT_DEPARTURES_COUNT = 8;

    public function getDeparturesForStop(Stop $stop, \DateTime $from, int $count = 8)
    {
        $query = $this->em
            ->createQueryBuilder()
            ->select('s')
            ->from(TripStopEntity::class, 's')
            ->where('s.arrival >= :from')
            ->andWhere('s.stop = :stop')
            ->setMaxResults($count)
            ->getQuery()
        ;

        $schedule = collect($query->execute([
            'from' => $from,
            'stop' => $this->reference(StopEntity::class, $stop),
        ]));

        return $schedule->map(ref([$this, 'convert']));
    }
}