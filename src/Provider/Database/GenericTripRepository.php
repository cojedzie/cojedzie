<?php

namespace App\Provider\Database;

use App\Entity\TripEntity;
use App\Model\Trip;
use App\Provider\TripRepository;

class GenericTripRepository extends DatabaseRepository implements TripRepository
{
    public function getById(string $id): Trip
    {
        $id   = $this->id->generate($this->provider, $id);

        $trip = $this->em
            ->createQueryBuilder()
            ->from(TripEntity::class, 't')
            ->join('t.stops', 'ts')
            ->select('t', 'ts')
            ->where('t.id = :id')
            ->getQuery()
            ->setParameter('id', $id)
            ->getOneOrNullResult();

        return $this->convert($trip);
    }
}
