<?php


namespace App\Provider;

use App\Entity\ProviderEntity;
use App\Provider\Database\GenericLineRepository;
use App\Provider\Database\GenericStopRepository;
use App\Provider\ZtmGdansk\{ZtmGdanskDepartureRepository, ZtmGdanskMessageRepository};
use Doctrine\ORM\EntityManagerInterface;

class ZtmGdanskProvider implements Provider
{
    private $lines;
    private $departures;
    private $stops;
    private $messages;

    public function __construct(
        EntityManagerInterface $em,
        GenericLineRepository $lines,
        GenericStopRepository $stops,
        ZtmGdanskMessageRepository $messages
    ) {
        $provider = $em->getReference(ProviderEntity::class, 'ztm-gdansk');

        $lines = $lines->withProvider($provider);
        $stops = $stops->withProvider($provider);

        $this->lines      = $lines;
        $this->departures = new ZtmGdanskDepartureRepository($lines);
        $this->stops      = $stops;
        $this->messages   = $messages;
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
}