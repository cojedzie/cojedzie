<?php

namespace App\Provider\Database;

use App\Entity\StopEntity;
use App\Entity\StopInTrack;
use App\Entity\TrackEntity;
use App\Modifier\Modifier;
use App\Model\Track;
use App\Provider\TrackRepository;
use Tightenco\Collect\Support\Collection;
use Kadet\Functional as f;
use function App\Functions\encapsulate;

class GenericTrackRepository extends DatabaseRepository implements TrackRepository
{
    public function getByStop($stop): Collection
    {
        $reference = f\apply(f\ref([$this, 'reference']), StopEntity::class);

        $tracks = $this->em->createQueryBuilder()
            ->from(StopInTrack::class, 'st')
            ->join('st.track', 't')
            ->where('st.stop in (:stop)')
            ->select(['st', 't'])
            ->getQuery()
            ->execute(['stop' => array_map($reference, encapsulate($stop))]);

        return collect($tracks)->map(function (StopInTrack $entity) {
            return [ $this->convert($entity->getTrack()), $entity->getOrder() ];
        });
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
