<?php

namespace App\Provider\ZtmGdansk;

use App\Entity\LineEntity;
use App\Entity\OperatorEntity;
use App\Entity\ProviderEntity;
use App\Entity\StopEntity;
use App\Entity\StopInTrack;
use App\Entity\TrackEntity;
use App\Entity\TripEntity;
use App\Entity\TripStopEntity;
use App\Event\DataUpdateEvent;
use App\Model\Line as LineModel;
use App\Model\Location;
use App\Service\DataUpdater;
use App\Service\IdUtils;
use Carbon\Carbon;
use Cerbero\JsonObjects\JsonObjects;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tightenco\Collect\Support\Collection;
use function Cerbero\JsonObjects\JsonObjects;
use function Kadet\Functional\ref;

class ZtmGdanskDataUpdateSubscriber implements EventSubscriberInterface
{
    const BASE_URL = 'https://ckan.multimediagdansk.pl/dataset/c24aa637-3619-4dc2-a171-a23eec8f2172/resource';

    const OPERATORS_URL       = self::BASE_URL."/dff5f71f-0134-4ef3-8116-73c1a8e929a5/download/agencies.json";
    const LINES_URL           = self::BASE_URL."/22313c56-5acf-41c7-a5fd-dc5dc72b3851/download/routes.json";
    const STOPS_URL           = self::BASE_URL."/4c4025f0-01bf-41f7-a39f-d156d201b82b/download/stops.json";
    const TRACKS_URL          = self::BASE_URL."/b15bb11c-7e06-4685-964e-3db7775f912f/download/trips.json";
    const STOPS_IN_TRACKS_URL = self::BASE_URL."/3115d29d-b763-4af5-93f6-763b835967d6/download/stopsintrips.json";

    const SCHEDULE_URL        = "http://ckan2.multimediagdansk.pl/stopTimes";

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

    public function update(DataUpdateEvent $event)
    {
        ini_set('memory_limit', '2G');

        $output = $event->getOutput();

        $provider = ProviderEntity::createFromArray([
            'name'  => $this->provider->getName(),
            'class' => ZtmGdanskProvider::class,
            'id'    => $this->provider->getIdentifier(),
        ]);

        $this->em->persist($provider);

        $save = ref([$this->em, 'persist']);

        $this->getOperators($provider, $event)->each($save);
        $this->getStops($provider, $event)->each($save);
        $this->getTracks($provider, $event)->each($save);
        $lines = $this->getLines($provider, $event)->each($save);

        $output->write('Flushing all things into database...');
        $this->em->flush();
        $this->em->clear();
        $output->writeln('done');

        $this->updateSchedule($provider, $lines, $event);
    }

    private function getOperators(ProviderEntity $provider, DataUpdateEvent $event)
    {
        $output = $event->getOutput();
        $output->write('Obtaining operators from ZTM Gdańsk...');
        $operators = file_get_contents(self::OPERATORS_URL);
        $operators = json_decode($operators, true)['agency'];
        $output->writeln(sprintf('done (%d)', count($operators)));

        $this->logger->info(sprintf('Saving %s operators from ZTM Gdańsk', count($operators)));

        return collect($operators)->map(function ($operator) use ($provider) {
            return OperatorEntity::createFromArray([
                'id'       => $this->ids->generate($provider, $operator['agencyId']),
                'name'     => $operator['agencyName'],
                'provider' => $provider,
            ]);
        });
    }

    private function getLines(ProviderEntity $provider, DataUpdateEvent $event)
    {
        $output = $event->getOutput();
        $output->write('Obtaining lines from ZTM Gdańsk... ');
        $lines = file_get_contents(self::LINES_URL);
        $lines = json_decode($lines, true)[date('Y-m-d')]['routes'];
        $output->writeln(sprintf('done (%d)', count($lines)));

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

    private function getStops(ProviderEntity $provider, DataUpdateEvent $event)
    {
        $output = $event->getOutput();

        $output->write('Obtaining stops from ZTM Gdańsk... ');
        $stops = file_get_contents(self::STOPS_URL);
        $stops = json_decode($stops, true)[date('Y-m-d')]['stops'];
        $output->writeln(sprintf('done (%d)', count($stops)));

        $this->logger->debug(sprintf("Saving %d stops tracks from ZTM Gdańsk.", count($stops)));
        return collect($stops)
            ->filter(function ($stop) {
                return $stop['nonpassenger'] !== 1
                    && $stop['virtual'] !== 1
                    && $stop['depot'] !== 1;
            })
            ->map(function ($stop) use ($provider) {
                $name = trim($stop['stopName'] ?? $stop['stopDesc']);

                return StopEntity::createFromArray([
                    'id'        => $this->ids->generate($provider, $stop['stopId']),
                    'name'      => $name,
                    'variant'   => trim($stop['zoneName'] == 'Gdańsk' ? $stop['stopCode'] ?? $stop['subName'] : null),
                    'latitude'  => $stop['stopLat'],
                    'longitude' => $stop['stopLon'],
                    'onDemand'  => (bool)$stop['onDemand'],
                    'provider'  => $provider,
                    'group'     => $name,
                ]);
            })
        ;
    }

    public function getTracks(ProviderEntity $provider, DataUpdateEvent $event, $stops = [])
    {
        $output = $event->getOutput();

        $output->write('Obtaining tracks from ZTM Gdańsk... ');
        $tracks = file_get_contents(self::TRACKS_URL);
        $tracks = json_decode($tracks, true)[date('Y-m-d')]['trips'];
        $output->writeln(sprintf('done (%d)', count($tracks)));
        $this->logger->debug(sprintf("Got %d tracks from ZTM Gdańsk.", count($tracks)));

        $output->write('Obtaining stops associations... ');
        $stops = file_get_contents(self::STOPS_IN_TRACKS_URL);
        $stops = json_decode($stops, true)[date('Y-m-d')]['stopsInTrip'];
        $output->writeln(sprintf('done (%d)', count($stops)));
        $this->logger->debug(sprintf("Got %d stops in all tracks from ZTM Gdańsk.", count($stops)));

        $stops = collect($stops)->groupBy(function ($stop) {
            return sprintf("R%sT%s", $stop['routeId'], $stop['tripId']);
        });

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
                    // HACK! Gdynia has 0 based sequence
                    'order' => $stop['stopSequence'] + (int)($stop['stopId'] > 30000),
                ]);
            });

            $entity->setStopsInTrack($stops->all());

            return $entity;
        });
    }

    public function saveScheduleForLine(ProviderEntity $provider, LineEntity $line)
    {
        $url = sprintf("%s?%s", self::SCHEDULE_URL, http_build_query([
            'date'    => date('Y-m-d'),
            'routeId' => $this->ids->of($line),
        ]));

        $schedule = JsonObjects::from($url, 'stopTimes.*');
        $trips = new Collection();

        $schedule->each(function ($stop) use ($provider, $line, &$trips, &$ids) {
            $id     = sprintf('%s-%s-%d', $stop['busServiceName'], $stop['tripId'], $stop['order']);
            $trip   = $trips[$id] ?? $trips[$id] = (function () use ($stop, $id, $provider) {
                $trip = TripEntity::createFromArray([
                    'id'       => $this->ids->generate($provider, $id),
                    'operator' => $this->em->getReference(
                        OperatorEntity::class,
                        $this->ids->generate($provider, $stop['agencyId'])
                    ),
                    'track'    => $this->em->getReference(
                        TrackEntity::class,
                        $this->ids->generate($provider, sprintf('R%sT%s', $stop['routeId'], $stop['tripId']))
                    ),
                ]);

                $this->em->persist($trip);

                return $trip;
            })();

            $base = Carbon::create(1899, 12, 30, 00, 00, 00);
            $date = Carbon::createFromFormat('Y-m-d', $stop['date'], 'Europe/Warsaw')->setTime(00, 00, 00);

            $arrival   = $base->diff(Carbon::createFromTimeString($stop['arrivalTime']));
            $departure = $base->diff(Carbon::createFromTimeString($stop['departureTime']));

            $arrival   = (clone $date)->add($arrival);
            $departure = (clone $date)->add($departure);

            $entity = TripStopEntity::createFromArray([
                'trip'      => $trip,
                'stop'      => $this->em->getReference(
                    StopEntity::class,
                    $this->ids->generate($provider, $stop['stopId'])
                ),
                'order'     => $stop['stopSequence'],
                'arrival'   => $arrival->tz('UTC'),
                'departure' => $departure->tz('UTC'),
            ]);

            $entity->setTrip($trip);

            $this->em->persist($entity);
        });

        $this->logger->debug(sprintf('Got schedule for line %s from ZTM Gdańsk', $line->getId()));

        $this->em->flush();
        $this->em->clear();

        gc_collect_cycles();
    }

    public static function getSubscribedEvents()
    {
        return [
            DataUpdater::UPDATE_EVENT => 'update',
        ];
    }

    private function updateSchedule(ProviderEntity $provider, Collection $lines, DataUpdateEvent $event)
    {
        $event->getOutput()->writeln(sprintf("Obtaining schedule for %d lines...", count($lines)));
        $progress = new ProgressBar($event->getOutput(), $lines->count());
        $progress->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%, line %line%');
        $progress->start();
        /** @var LineEntity $line */
        foreach ($lines as $line) {
            $progress->setMessage($line->getSymbol(), 'line');
            $progress->display();

            $this->saveScheduleForLine($provider, $line);
            $progress->advance();
        }

        $progress->finish();
        $event->getOutput()->writeln("");
        $event->getOutput()->writeln("done");
    }
}
