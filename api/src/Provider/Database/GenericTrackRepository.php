<?php

namespace App\Provider\Database;

use App\Entity\TrackStopEntity;
use App\Entity\TrackEntity;
use App\Model\TrackStop;
use App\Modifier\Modifier;
use App\Model\Track;
use App\Provider\TrackRepository;
use Tightenco\Collect\Support\Collection;

class GenericTrackRepository extends DatabaseRepository implements TrackRepository
{
    public function stops(Modifier ...$modifiers): Collection
    {
        $builder = $this->em
            ->createQueryBuilder()
            ->from(TrackStopEntity::class, 'track_stop')
            ->select(['track_stop']);

        return $this->allFromQueryBuilder($builder, $modifiers, [
            'alias'  => 'track_stop',
            'entity' => TrackStopEntity::class,
            'type'   => TrackStop::class,
        ]);
    }

    public function all(Modifier ...$modifiers): Collection
    {
        $builder = $this->em
            ->createQueryBuilder()
            ->from(TrackEntity::class, 'track')
            ->select('track');

        return $this->allFromQueryBuilder($builder, $modifiers, [
            'alias'  => 'track',
            'entity' => TrackEntity::class,
            'type'   => Track::class,
        ]);
    }
}
