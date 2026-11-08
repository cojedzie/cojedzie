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

namespace App\Dto;

/**
 * What is known about a departure: only its plan, a live estimate, or that it has left.
 */
enum DepartureState: string
{
    // no live data: the departure is expected at its scheduled time
    case Planned = 'planned';
    // a live estimate is known
    case Realtime = 'realtime';
    // the departure has left; its estimate, when known, is the last one recorded
    case Departed = 'departed';
}
