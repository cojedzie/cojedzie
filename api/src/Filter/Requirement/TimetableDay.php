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

namespace App\Filter\Requirement;

use App\Exception\InvalidArgumentException;

/**
 * Timetable entries of one day, in the provider's time zone, selected by calendar or service day (ADR 0029).
 */
final readonly class TimetableDay implements Requirement
{
    /**
     * @param ?string $date Y-m-d, null for today
     */
    public function __construct(
        public ?string $date = null,
        public DayMode $mode = DayMode::Calendar,
    ) {
        if ($date !== null && !self::isDate($date)) {
            throw new InvalidArgumentException(sprintf('Expected a date as YYYY-MM-DD, got "%s".', $date));
        }
    }

    private static function isDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }
}
