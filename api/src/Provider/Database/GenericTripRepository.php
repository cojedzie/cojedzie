<?php

namespace App\Provider\Database;

use App\Entity\TripEntity;
use App\Model\Trip;
use App\Modifier\Modifier;
use App\Provider\TripRepository;
use Illuminate\Support\Collection;

class GenericTripRepository extends DatabaseRepository implements TripRepository
{
    public function all(Modifier ...$modifiers): Collection
    {
        $builder = $this->em
            ->createQueryBuilder()
            ->from(TripEntity::class, 'trip')
            ->select('trip');

        return $this->allFromQueryBuilder($builder, $modifiers, [
            'alias'  => 'trip',
            'entity' => TripEntity::class,
            'type'   => Trip::class,
        ]);
    }
}
