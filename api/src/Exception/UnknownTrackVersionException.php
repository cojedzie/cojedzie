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

namespace App\Exception;

/**
 * Every trip of a line+day refers to a track version the track importer didn't write, so the day isn't recorded as
 * imported and the next import downloads it again.
 */
final class UnknownTrackVersionException extends \RuntimeException
{
    public function __construct(
        public readonly string $lineId,
        public readonly string $date,
        public readonly int $trips,
    ) {
        parent::__construct(sprintf('All %d trips of line %s on %s have an unknown track, left for the next import', $trips, $lineId, $date));
    }
}
