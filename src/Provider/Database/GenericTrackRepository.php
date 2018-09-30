<?php

namespace App\Provider\Database;

use App\Entity\LineEntity;
use App\Entity\StopEntity;
use App\Entity\StopInTrack;
use App\Entity\TrackEntity;
use function App\Functions\encapsulate;
use App\Model\Stop;
use App\Model\Track;
use App\Provider\TrackRepository;
use Tightenco\Collect\Support\Collection;
use Kadet\Functional as f;

class GenericTrackRepository extends DatabaseRepository implements TrackRepository
{
    public function getAll(): Collection
    {
        $tracks = $this->em->getRepository(TrackEntity::class)->findAll();

        return collect($tracks)->map(f\ref([$this, 'convert']));
    }

    public function getById($id): Track
    {
        // TODO: Implement getById() method.
    }

    public function getManyById($ids): Collection
    {
        // TODO: Implement getManyById() method.
    }

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

    public function getByLine($line): Collection
    {
        $reference = f\apply(f\ref([$this, 'reference']), LineEntity::class);

        $tracks = $this->em->createQueryBuilder()
            ->from(StopInTrack::class, 'st')
            ->join('st.track', 't')
            ->join('t.stops', 's')
            ->where('st.line in (:line)')
            ->select(['st', 't', 's'])
            ->getQuery()
            ->execute(['stop' => array_map($reference, encapsulate($line))]);

        return collect($tracks)->map(f\ref([$this, 'convert']));
    }
}