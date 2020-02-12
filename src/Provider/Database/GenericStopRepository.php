<?php

namespace App\Provider\Database;

use App\Entity\StopEntity;
use App\Entity\TrackEntity;
use App\Model\Destination;
use App\Model\Stop;
use App\Provider\StopRepository;
use Kadet\Functional as f;
use Kadet\Functional\Transforms as t;
use Tightenco\Collect\Support\Collection;

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
            ->select('t', 'tl', 'f', 'fs', 'ts')
            ->from(TrackEntity::class, 't')
            ->join('t.stopsInTrack', 'ts')
            ->join('t.line', 'tl')
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
                return $tracks
                    ->groupBy(function (TrackEntity $track) {
                        return $track->getFinal()->getStop()->getId();
                    })->map(function (Collection $tracks, $id) {
                        return Destination::createFromArray([
                            'stop'  => $this->convert($tracks->first()->getFinal()->getStop()),
                            'lines' => $tracks
                                ->map(t\property('line'))
                                ->unique(t\property('id'))
                                ->map(f\ref([$this, 'convert']))
                                ->values(),
                        ]);
                    })->values();
            });

        return collect($stops)->map(f\ref([$this, 'convert']))->each(function (Stop $stop) use ($destinations) {
            $stop->setDestinations($destinations[$this->id->generate($this->provider, $stop->getId())]);
        });
    }

    protected static function getHandlers()
    {
        return [];
    }
}
