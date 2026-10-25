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

namespace App\Tests\Behat\Support;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Symfony\Component\Clock\MockClock;

/**
 * The frozen "now" of a scenario and the time zone its tables are written in.
 */
final class FrozenTime
{
    private string $zone = 'Europe/Warsaw';

    public function __construct(
        private readonly MockClock $clock,
    ) {
    }

    public function freeze(string $time, string $zone): void
    {
        $this->zone = $zone;
        $now        = CarbonImmutable::parse($time, $zone);

        $this->clock->modify('@' . $now->getTimestamp());
        Carbon::setTestNow($now);
    }

    public function now(): CarbonImmutable
    {
        return CarbonImmutable::instance($this->clock->now())->setTimezone($this->zone);
    }

    /**
     * Time of day ("HH:MM") as the nearest such moment around now, so a table can list departures after midnight.
     */
    public function at(string $time): Carbon
    {
        $now    = $this->now();
        $moment = Carbon::parse($now->format('Y-m-d') . ' ' . $time, $this->zone);

        if ($moment->lessThan($now->subHours(12))) {
            $moment->addDay();
        }

        return $moment;
    }

    /**
     * Formats an API timestamp as a time of day in the scenario's zone.
     */
    public function local(?string $timestamp): string
    {
        return $timestamp === null ? '' : Carbon::parse($timestamp)->setTimezone($this->zone)->format('H:i');
    }
}
