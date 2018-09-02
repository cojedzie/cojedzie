<?php


namespace App\Provider;

use App\Provider\ZtmGdansk\{ZtmGdanskDepartureRepository,
    ZtmGdanskLineRepository,
    ZtmGdanskMessageRepository,
    ZtmGdanskStopRepository};

class ZtmGdanskProvider implements Provider
{
    private $lines;
    private $departures;
    private $stops;
    private $messages;

    public function __construct(
        ZtmGdanskLineRepository $lines,
        ZtmGdanskDepartureRepository $departures,
        ZtmGdanskStopRepository $stops,
        ZtmGdanskMessageRepository $messages
    ) {
        $this->lines      = $lines;
        $this->departures = $departures;
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