<?php

namespace App\Provider\Dummy;

use App\Exception\NotSupportedException;
use App\Provider\DepartureRepository;
use App\Provider\LineRepository;
use App\Provider\MessageRepository;
use App\Provider\Provider;
use App\Provider\StopRepository;
use App\Provider\TrackRepository;
use Carbon\Carbon;

class DummyProvider implements Provider
{
    private $departures;
    private $stops;

    /**
     * DummyProvider constructor.
     *
     * @param $departures
     */
    public function __construct(DummyDepartureRepository $departures, DummyStopRepository $stops)
    {
        $this->departures = $departures;
        $this->stops      = $stops;
    }

    public function getDepartureRepository(): DepartureRepository
    {
        return $this->departures;
    }

    public function getLineRepository(): LineRepository
    {
        throw new NotSupportedException();
    }

    public function getStopRepository(): StopRepository
    {
        return $this->stops;
    }

    public function getMessageRepository(): MessageRepository
    {
        return new DummyMessageRepository();
    }

    public function getTrackRepository(): TrackRepository
    {
        throw new NotSupportedException();
    }

    public function getName(): string
    {
        return "Dummy data for debugging";
    }

    public function getShortName(): string
    {
        return "dummy";
    }

    public function getIdentifier(): string
    {
        return "dummy";
    }

    public function getAttribution(): ?string
    {
        return null;
    }

    public function getLastUpdate(): ?Carbon
    {
        return null;
    }
}