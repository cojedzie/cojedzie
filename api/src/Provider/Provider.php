<?php

namespace App\Provider;

use App\Model\Location;
use Carbon\Carbon;

interface Provider
{
    public function getDepartureRepository(): DepartureRepository;
    public function getLineRepository(): LineRepository;
    public function getStopRepository(): StopRepository;
    public function getMessageRepository(): MessageRepository;
    public function getTrackRepository(): TrackRepository;
    public function getTripRepository(): TripRepository;

    public function getName(): string;
    public function getShortName(): string;
    public function getIdentifier(): string;
    public function getAttribution(): ?string;
    public function getLocation(): Location;

    public function getLastUpdate(): ?Carbon;
}
