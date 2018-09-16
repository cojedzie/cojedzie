<?php

namespace App\Provider\ZtmGdansk;

use App\Entity\LineEntity;
use App\Entity\OperatorEntity;
use App\Entity\ProviderEntity;
use App\Entity\StopEntity;
use App\Entity\StopInTrack;
use App\Entity\TrackEntity;
use App\Model\Line as LineModel;
use App\Provider\ZtmGdanskProvider;
use App\Service\DataUpdater;
use App\Service\IdUtils;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ZtmGdanskDataUpdateSubscriber implements EventSubscriberInterface
{
    const OPERATORS_URL       = "http://91.244.248.19/dataset/c24aa637-3619-4dc2-a171-a23eec8f2172/resource/8b3aa347-3bb7-4c58-9113-d47458ec1fc3/download/agency.json";
    const LINES_URL           = "http://91.244.248.19/dataset/c24aa637-3619-4dc2-a171-a23eec8f2172/resource/4128329f-5adb-4082-b326-6e1aea7caddf/download/routes.json";
    const STOPS_URL           = "http://91.244.248.19/dataset/c24aa637-3619-4dc2-a171-a23eec8f2172/resource/cd4c08b5-460e-40db-b920-ab9fc93c1a92/download/stops.json";
    const TRACKS_URL          = "http://91.244.248.19/dataset/c24aa637-3619-4dc2-a171-a23eec8f2172/resource/33618472-342c-4a4a-ba88-a911ec0ad5a7/download/trips.json";
    const STOPS_IN_TRACKS_URL = "http://91.244.248.19/dataset/c24aa637-3619-4dc2-a171-a23eec8f2172/resource/0f2de200-e78b-4183-ae0d-071d7f07fc3f/download/stopsintrips.json";

    private $em;
    private $ids;
    private $logger;
    private $provider;

    /**
     * ZtmGdanskDataUpdateSubscriber constructor.
     *
     * @param $provider
     * @param $em
     */
    public function __construct(
        EntityManagerInterface $em,
        IdUtils $ids,
        LoggerInterface $logger,
        ZtmGdanskProvider $provider
    ) {
        $this->em       = $em;
        $this->ids      = $ids;
        $this->logger   = $logger;
        $this->provider = $provider;
    }

    public function update()
    {
        $provider = ProviderEntity::createFromArray([
            'name'  => $this->provider->getName(),
            'class' => ZtmGdanskProvider::class,
            'id'    => $this->provider->getIdentifier(),
        ]);

        $this->em->persist($provider);

        $save = [$this->em, 'persist'];

        $this->getOperators($provider)->each($save);
        $this->getLines($provider)->each($save);
        $this->getStops($provider)->each($save);
        $this->getTracks($provider)->each($save);

        $this->em->flush();
    }

    private function getOperators(ProviderEntity $provider)
    {
        $this->logger->info('Obtaining operators from ZTM Gdańsk');

        $operators = file_get_contents(self::OPERATORS_URL);
        $operators = json_decode($operators, true)['agency'];

        $this->logger->info(sprintf('Saving %s operators from ZTM Gdańsk', count($operators)));

        return collect($operators)->map(function ($operator) use ($provider) {
            return OperatorEntity::createFromArray([
                'id'       => $this->ids->generate($provider, $operator['agencyId']),
                'name'     => $operator['agencyName'],
                'provider' => $provider,
            ]);
        });
    }

    private function getLines(ProviderEntity $provider)
    {
        $this->logger->info('Obtaining lines from ZTM Gdańsk');

        $lines = file_get_contents(self::LINES_URL);
        $lines = json_decode($lines, true)[date('Y-m-d')]['routes'];

        $this->logger->info(sprintf('Saving %s lines from ZTM Gdańsk', count($lines)));

        return collect($lines)->map(function ($line) use ($provider) {
            $symbol   = $line['routeShortName'];
            $operator = $this->em->getReference(
                OperatorEntity::class,
                $this->ids->generate($provider, $line['agencyId'])
            );
            $type     = [
                2 => LineModel::TYPE_TRAM,
                5 => LineModel::TYPE_TROLLEYBUS,
            ];

            return LineEntity::createFromArray([
                'id'          => $this->ids->generate($provider, $line['routeId']),
                'symbol'      => $symbol,
                'description' => $line['routeLongName'],
                'type'        => $type[$line['agencyId']] ?? LineModel::TYPE_BUS,
                'night'       => preg_match('/^N\d{1,3}$/', $symbol),
                'fast'        => preg_match('/^[A-MO-Z]$/', $symbol),
                'operator'    => $operator,
                'provider'    => $provider,
            ]);
        });
    }

    private function getStops(ProviderEntity $provider)
    {
        $this->logger->info('Obtaining stops from ZTM Gdańsk');

        $stops = file_get_contents(self::STOPS_URL);
        $stops = json_decode($stops, true)[date('Y-m-d')]['stops'];

        $this->logger->info(sprintf('Saving %d stops from ZTM Gdańsk', count($stops)));

        return collect($stops)->map(function ($stop) use ($provider) {
            return StopEntity::createFromArray([
                'id'        => $this->ids->generate($provider, $stop['stopId']),
                'name'      => trim($stop['stopName'] ?? $stop['stopDesc']),
                'variant'   => trim($stop['zoneName'] == 'Gdańsk' ? $stop['stopCode'] : null),
                'latitude'  => $stop['stopLat'],
                'longitude' => $stop['stopLon'],
                'onDemand'  => (bool)$stop['onDemand'],
                'provider'  => $provider,
            ]);
        });
    }

    public function getTracks(ProviderEntity $provider)
    {
        $this->logger->info('Obtaining tracks from ZTM Gdańsk');

        $tracks = file_get_contents(self::TRACKS_URL);
        $tracks = json_decode($tracks, true)[date('Y-m-d')]['trips'];

        $this->logger->info('Obtaining stops associations from ZTM Gdańsk');

        $stops = file_get_contents(self::STOPS_IN_TRACKS_URL);
        $stops = json_decode($stops, true)[date('Y-m-d')]['stopsInTrip'];

        $stops = collect($stops)->groupBy(function ($stop) {
            return sprintf("R%sT%s", $stop['routeId'], $stop['tripId']);
        });

        $this->logger->info(sprintf('Saving %d tracks from ZTM Gdańsk', count($stops)));

        return collect($tracks)->map(function ($track) use ($provider, $stops) {
            $entity = TrackEntity::createFromArray([
                'id'          => $this->ids->generate($provider, $track['id']),
                'line'        => $this->em->getReference(
                    LineEntity::class,
                    $this->ids->generate($provider, $track['routeId'])
                ),
                'description' => preg_replace('/\(\d+\)/', '', $track['tripHeadsign']),
                'provider'    => $provider,
            ]);

            $stops = $stops->get($track['id'])->map(function ($stop) use ($entity, $provider) {
                return StopInTrack::createFromArray([
                    'stop'  => $this->em->getReference(
                        StopEntity::class,
                        $this->ids->generate($provider, $stop['stopId'])
                    ),
                    'track' => $entity,
                    'order' => $stop['stopSequence'],
                ]);
            });

            $entity->setStopsInTrack($stops->all());

            return $entity;
        });
    }

    public static function getSubscribedEvents()
    {
        return [
            DataUpdater::UPDATE_EVENT => 'update',
        ];
    }
}