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
