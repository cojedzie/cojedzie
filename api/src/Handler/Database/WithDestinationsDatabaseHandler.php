<?php

namespace App\Handler\Database;

use App\Entity\TrackEntity;
use App\Event\PostProcessEvent;
use App\Handler\PostProcessingHandler;
use App\Model\Destination;
use App\Model\Stop;
use App\Service\CacheableConverter;
use App\Service\Converter;
use App\Service\IdUtils;
use Doctrine\ORM\EntityManagerInterface;
use Kadet\Functional as f;
use Kadet\Functional\Transforms as t;
use Tightenco\Collect\Support\Collection;

class WithDestinationsDatabaseHandler implements PostProcessingHandler
{
    private $em;
    private $converter;
    private $id;

    public function __construct(EntityManagerInterface $entityManager, Converter $converter, IdUtils $id)
    {
        $this->em = $entityManager;
        $this->converter = $converter;
        $this->id = $id;

        if ($this->converter instanceof CacheableConverter) {
            $this->converter = clone $this->converter;
            $this->converter->flushCache();
        }
    }

    public function postProcess(PostProcessEvent $event)
    {
        $provider = $event->getMeta()['provider'];
        $stops = $event
            ->getData()
            ->map(t\property('id'))
            ->map(f\apply([$this->id, 'generate'], $provider))
            ->all();

        $destinations = collect($this->em->createQueryBuilder()
            ->select('t', 'tl', 'f', 'fs', 'ts')
            ->from(TrackEntity::class, 't')
            ->join('t.stopsInTrack', 'ts')
            ->join('t.line', 'tl')
            ->where('ts.stop IN (:stops)')
            ->join('t.final', 'f')
            ->join('f.stop', 'fs')
            ->getQuery()
            ->execute(['stops' => $stops]))
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
                            'stop'  => $this->converter->convert($tracks->first()->getFinal()->getStop()),
                            'lines' => $tracks
                                ->map(t\property('line'))
                                ->unique(t\property('id'))
                                ->map(f\ref([$this->converter, 'convert']))
                                ->values(),
                        ]);
                    })->values();
            });

        $event->getData()->each(function (Stop $stop) use ($provider, $destinations) {
            $stop->setDestinations($destinations[$this->id->generate($provider, $stop->getId())]);
        });
    }
}
