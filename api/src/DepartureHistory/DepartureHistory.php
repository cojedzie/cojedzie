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

namespace App\DepartureHistory;

use App\Provider\Live\LiveDeparture;

/**
 * Where recorded departures are kept: one per departure per stop, the newest observation wins.
 */
interface DepartureHistory
{
    /**
     * Whether a storage is configured; without one nothing is recorded.
     */
    public function isEnabled(): bool;

    /**
     * Makes the storage ready to record: brings its schema up to date and applies the retention.
     *
     * @throws DepartureHistoryException
     */
    public function prepare(): void;

    /**
     * @param iterable<LiveDeparture> $departures
     *
     * @throws DepartureHistoryException
     */
    public function record(string $provider, \DateTimeImmutable $observedAt, iterable $departures): void;
}
