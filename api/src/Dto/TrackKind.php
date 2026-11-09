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
 * What a track is to riders, see ADR 0031.
 */
enum TrackKind: string
{
    // the track riders know as the line in one direction
    case Main = 'main';
    // a shortened or otherwise different run, and any track a provider can't tell
    case Side = 'side';
    // a run to or from the depot, which riders can't take
    case NonPassenger = 'non-passenger';

    /**
     * Kinds of the tracks riders can take.
     *
     * @return list<self>
     */
    public static function passenger(): array
    {
        return [self::Main, self::Side];
    }
}
