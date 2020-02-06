<?php

namespace App\Provider\Database;

use App\Entity\StopEntity;
use App\Entity\TrackEntity;
use App\Model\Stop;
use App\Provider\StopRepository;
use Tightenco\Collect\Support\Collection;
use Kadet\Functional as f;
use Kadet\Functional\Transforms as t;

class GenericStopRepository extends DatabaseRepository implements StopRepository
{
    public function getAll(): Collection
    {
        $stops = $this->em->getRepository(StopEntity::class)->findAll();

        return collect($stops)->map(f\ref([$this, 'convert']));
    }

    public function getById($id): ?Stop
    {
        $id   = $this->id->generate($this->provider, $id);
        $stop = $this->em->getRepository(StopEntity::class)->find($id);

        return $this->convert($stop);
    }

    public function getManyById($ids): Collection
    {
        $ids   = collect($ids)->map(f\apply(f\ref([$this->id, 'generate']), $this->provider));
        $stops = $this->em->getRepository(StopEntity::class)->findBy(['id' => $ids->all()]);

        return collect($stops)->map(f\ref([$this, 'convert']));
    }

    public function findByName(string $name): Collection
    {
        $query = $this->em->createQueryBuilder()
            ->select('s')
            ->from(StopEntity::class, 's')
            ->where('s.name LIKE :name')
            ->getQuery();

        $stops = collect($query->execute([':name' => "%$name%"]));

        $destinations = collect($this->em->createQueryBuilder()
            ->select('t', 'f', 'fs', 'ts')
            ->from(TrackEntity::class, 't')
            ->join('t.stopsInTrack', 'ts')
            ->where('ts.stop IN (:stops)')
            ->join('t.final', 'f')
            ->join('f.stop', 'fs')
            ->getQuery()
            ->execute(['stops' => $stops->map(t\property('id'))->all()]))
            ->reduce(function ($grouped, TrackEntity $track) {
                foreach ($track->getStopsInTrack()->map(t\property('stop'))->map(t\property('id')) as $stop) {
                    $grouped[$stop] = ($grouped[$stop] ?? collect())->add($track);
                }

                return $grouped;
            }, collect())
            ->map(function (Collection $tracks) {
                return $tracks->map(function (TrackEntity $track) {
                    return $this->convert($track->getFinal()->getStop());
                })->unique()->values();
            });

        return collect($stops)->map(f\ref([$this, 'convert']))->each(function (Stop $stop) use ($destinations) {
            $stop->setDestinations($destinations[$this->id->generate($this->provider, $stop->getId())]);
        });
    }
}
