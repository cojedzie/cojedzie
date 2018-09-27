<?php

namespace App\Provider;

interface Provider
{
    public function getDepartureRepository(): DepartureRepository;
    public function getLineRepository(): LineRepository;
    public function getStopRepository(): StopRepository;
    public function getMessageRepository(): MessageRepository;
    public function getTrackRepository(): TrackRepository;

    public function getName(): string;
    public function getShortName(): string;
    public function getIdentifier(): string;
    public function getAttribution(): ?string;
}