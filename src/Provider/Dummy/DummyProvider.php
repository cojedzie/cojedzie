<?php

namespace App\Provider\Dummy;

use App\Exception\NotSupportedException;
use App\Provider\DepartureRepository;
use App\Provider\LineRepository;
use App\Provider\MessageRepository;
use App\Provider\Provider;
use App\Provider\StopRepository;
use App\Provider\TrackRepository;

class DummyProvider implements Provider
{
    public function getDepartureRepository(): DepartureRepository
    {
        throw new NotSupportedException();
    }

    public function getLineRepository(): LineRepository
    {
        throw new NotSupportedException();
    }

    public function getStopRepository(): StopRepository
    {
        throw new NotSupportedException();
    }

    public function getMessageRepository(): MessageRepository
    {
        return new DummyMessageRepository();
    }

    public function getTrackRepository(): TrackRepository
    {
        throw new NotSupportedException();
    }

    public function getName()
    {
        return "Dummy data for debugging";
    }

    public function getShortName()
    {
        return "dummy";
    }

    public function getIdentifier()
    {
        return "dummy";
    }
}