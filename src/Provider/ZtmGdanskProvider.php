<?php


namespace App\Provider;

use App\Entity\ProviderEntity;
use App\Provider\Database\GenericLineRepository;
use App\Provider\Database\GenericStopRepository;
use App\Provider\Database\GenericTrackRepository;
use App\Provider\ZtmGdansk\{ZtmGdanskDepartureRepository, ZtmGdanskMessageRepository};
use App\Service\Proxy\ReferenceFactory;
use Doctrine\ORM\EntityManagerInterface;

class ZtmGdanskProvider implements Provider
{
    private $lines;
    private $departures;
    private $stops;
    private $tracks;
    private $messages;

    public function getName()
    {
        return 'MZKZG - Trójmiasto';
    }

    public function getIdentifier()
    {
        return 'trojmiasto';
    }

    public function __construct(
        EntityManagerInterface $em,
        GenericLineRepository $lines,
        GenericStopRepository $stops,
        GenericTrackRepository $tracks,
        ZtmGdanskMessageRepository $messages,
        ReferenceFactory $referenceFactory
    ) {
        $provider = $em->getReference(ProviderEntity::class, $this->getIdentifier());

        $lines  = $lines->withProvider($provider);
        $stops  = $stops->withProvider($provider);
        $tracks = $tracks->withProvider($provider);

        $this->lines      = $lines;
        $this->departures = new ZtmGdanskDepartureRepository($lines, $referenceFactory);
        $this->stops      = $stops;
        $this->messages   = $messages;
        $this->tracks     = $tracks;
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
}