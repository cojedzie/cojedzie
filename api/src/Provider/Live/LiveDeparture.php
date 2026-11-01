<?php
/*
 * Copyright (C) 2026 Kacper Donat
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


declare(strict_types=1);

namespace App\Provider\Live;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * A departure as a provider's live feed reports it right now, in the provider's own ids.
 */
#[Exclude] // a value, not a service
final readonly class LiveDeparture
{
    public function __construct(
        public string $stopId,
        public string $lineId,
        // what riders see, e.g. 171 or C10
        public string $lineSymbol,
        // tells apart departures of the same line from the same stop at the same minute of a day
        public string $tripKey,
        public \DateTimeImmutable $scheduledAt,
        // null unless the status is realtime
        public ?\DateTimeImmutable $estimatedAt,
        public DepartureStatus $status,
        public string $headsign,
        public ?string $vehicle,
        // the vehicle's planned work for the day (a block), the closest the feeds come to a driver
        public ?string $duty,
    ) {
    }
}
