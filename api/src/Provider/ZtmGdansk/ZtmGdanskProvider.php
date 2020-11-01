<?php


namespace App\Provider\ZtmGdansk;

use App\Entity\ProviderEntity;
use App\Model\Location;
use App\Provider\Database\GenericLineRepository;
use App\Provider\Database\GenericScheduleRepository;
use App\Provider\Database\GenericStopRepository;
use App\Provider\Database\GenericTrackRepository;
use App\Provider\Database\GenericTripRepository;
use App\Provider\DepartureRepository;
use App\Provider\LineRepository;
use App\Provider\MessageRepository;
use App\Provider\Provider;
use App\Provider\StopRepository;
use App\Provider\TrackRepository;
use App\Provider\TripRepository;
use App\Service\Proxy\ReferenceFactory;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;

class ZtmGdanskProvider implements Provider
{
    private $lines;
    private $departures;
    private $stops;
    private $tracks;
    private $messages;

    /** @var ProviderEntity */
    private $entity;
    private $trips;

    public function getName(): string
    {
        return 'MZKZG - Trójmiasto';
    }

    public function getShortName(): string
    {
        return 'Trójmiasto';
    }

    public function getIdentifier(): string
    {
        return 'trojmiasto';
    }

    public function getAttribution(): string
    {
        return '<a href="http://ztm.gda.pl/otwarty_ztm">Otwarte Dane</a> Zarządu Transportu Miejskiego w Gdańsku';
    }

    public function getLocation(): Location
    {
        return new Location(18.6466, 54.3520);
    }

    public function __construct(
        EntityManagerInterface $em,
        GenericLineRepository $lines,
        GenericStopRepository $stops,
        GenericTrackRepository $tracks,
        GenericScheduleRepository $schedule,
        GenericTripRepository $trips,
        ZtmGdanskMessageRepository $messages,
        ReferenceFactory $referenceFactory
    ) {
        $provider = $em->getReference(ProviderEntity::class, $this->getIdentifier());

        $lines    = $lines->withProvider($provider);
        $stops    = $stops->withProvider($provider);
        $tracks   = $tracks->withProvider($provider);
        $schedule = $schedule->withProvider($provider);
        $trips    = $trips->withProvider($provider);

        $this->lines      = $lines;
        $this->departures = new ZtmGdanskDepartureRepository($lines, $schedule, $referenceFactory);
        $this->stops      = $stops;
        $this->messages   = $messages;
        $this->tracks     = $tracks;
        $this->entity     = $provider;
        $this->trips      = $trips;
    }

    public function getDepartureRepository(): DepartureRepository
    {
        return $this->departures;
    }

    public function getLineRepository(): LineRepository
    {
        return $this->lines;
    }

    public function getStopRepository(): StopRepository
    {
        return $this->stops;
    }

    public function getMessageRepository(): MessageRepository
    {
        return $this->messages;
    }

    public function getTrackRepository(): TrackRepository
    {
        return $this->tracks;
    }

    public function getTripRepository(): TripRepository
    {
        return $this->trips;
    }

    public function getLastUpdate(): ?Carbon
    {
        return $this->entity->getUpdateDate();
    }
}
