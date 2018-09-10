<?php

namespace App\Provider\Database;

use App\Entity\LineEntity;
use App\Entity\StopEntity;
use App\Entity\StopInTrack;
use App\Entity\TrackEntity;
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
        $tracks = $this->em->createQueryBuilder()
            ->from(StopInTrack::class, 'st')
            ->join('st.track', 't')
            ->where('st.stop = :stop')
            ->select(['st', 't'])
            ->getQuery()
            ->execute(['stop' => $this->reference(StopEntity::class, $stop)]);

        return collect($tracks)->map(function (StopInTrack $entity) {
            return [ $this->convert($entity->getTrack()), $entity->getOrder() ];
        });
    }

    public function getByLine($line): Collection
    {
        $tracks = $this->em->createQueryBuilder()
            ->from(StopInTrack::class, 'st')
            ->join('st.track', 't')
            ->join('t.stops', 's')
            ->where('st.line = :line')
            ->select(['st', 't', 's'])
            ->getQuery()
            ->execute(['stop' => $this->reference(LineEntity::class, $line)]);

        return collect($tracks)->map(f\ref([$this, 'convert']));
    }
}