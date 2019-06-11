<?php


namespace App\Provider\ZtmGdansk;

use App\Entity\ProviderEntity;
use App\Provider\Database\GenericLineRepository;
use App\Provider\Database\GenericScheduleRepository;
use App\Provider\Database\GenericStopRepository;
use App\Provider\Database\GenericTrackRepository;
use App\Provider\DepartureRepository;
use App\Provider\LineRepository;
use App\Provider\MessageRepository;
use App\Provider\Provider;
use App\Provider\StopRepository;
use App\Provider\TrackRepository;
use App\Provider\ZtmGdansk\{ZtmGdanskDepartureRepository, ZtmGdanskMessageRepository};
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

    public function __construct(
        EntityManagerInterface $em,
        GenericLineRepository $lines,
        GenericStopRepository $stops,
        GenericTrackRepository $tracks,
        GenericScheduleRepository $schedule,
        ZtmGdanskMessageRepository $messages,
        ReferenceFactory $referenceFactory
    ) {
        $provider = $em->getReference(ProviderEntity::class, $this->getIdentifier());

        $lines    = $lines->withProvider($provider);
        $stops    = $stops->withProvider($provider);
        $tracks   = $tracks->withProvider($provider);
        $schedule = $schedule->withProvider($provider);

        $this->lines      = $lines;
        $this->departures = new ZtmGdanskDepartureRepository($lines, $referenceFactory);
        $this->stops      = $stops;
        $this->messages   = $messages;
        $this->tracks     = $tracks;
        $this->entity     = $provider;
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

    public function getLastUpdate(): ?Carbon
    {
        return $this->entity->getUpdateDate();
    }
}