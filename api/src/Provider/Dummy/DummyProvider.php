<?php
/*
 * Copyright (C) 2021 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Provider\Dummy;

use App\Dto\Location;
use App\Exception\NotSupportedException;
use App\Provider\DepartureRepository;
use App\Provider\LineRepository;
use App\Provider\MessageRepository;
use App\Provider\Provider;
use App\Provider\StopRepository;
use App\Provider\TrackRepository;
use App\Provider\TripRepository;
use Carbon\Carbon;

class DummyProvider implements Provider
{
    public function __construct(
        private readonly DummyDepartureRepository $departures,
        private readonly DummyStopRepository $stops
    ) {
    }

    #[\Override]
    public function getDepartureRepository(): DepartureRepository
    {
        return $this->departures;
    }

    #[\Override]
    public function getLineRepository(): LineRepository
    {
        throw new NotSupportedException();
    }

    #[\Override]
    public function getStopRepository(): StopRepository
    {
        return $this->stops;
    }

    #[\Override]
    public function getMessageRepository(): MessageRepository
    {
        return new DummyMessageRepository();
    }

    #[\Override]
    public function getTrackRepository(): TrackRepository
    {
        throw new NotSupportedException();
    }

    #[\Override]
    public function getName(): string
    {
        return "Dummy data for debugging";
    }

    #[\Override]
    public function getShortName(): string
    {
        return "dummy";
    }

    #[\Override]
    public function getIdentifier(): string
    {
        return "dummy";
    }

    #[\Override]
    public function getAttribution(): ?string
    {
        return null;
    }

    #[\Override]
    public function getLastUpdate(): ?Carbon
    {
        return null;
    }

    #[\Override]
    public function getLocation(): Location
    {
        return new Location(21.4474, 54.7837);
    }

    #[\Override]
    public function getTripRepository(): TripRepository
    {
        throw new NotSupportedException();
    }
}
