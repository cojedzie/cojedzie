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

/**
 * How a timetable day is selected (ADR 0029).
 */
enum DayMode: string
{
    /**
     * Departures at the stop between the local midnights of the date.
     */
    case Calendar = 'calendar';

    /**
     * Departures of trips whose service day is the date, including their runs after midnight.
     */
    case Service = 'service';

    /**
     * Both: the calendar day plus the runs of its service day after midnight.
     */
    case Extended = 'extended';
}
