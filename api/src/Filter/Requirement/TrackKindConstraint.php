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

use App\Dto\TrackKind;

/**
 * Restricts tracks, and what runs on them, to tracks of the given kinds. Rider-facing repositories apply
 * passenger() unless asked for other kinds, see ADR 0031.
 */
final readonly class TrackKindConstraint implements Requirement
{
    /**
     * @var list<TrackKind>
     */
    public array $kinds;

    public function __construct(TrackKind ...$kinds)
    {
        $this->kinds = array_values($kinds);
    }

    /**
     * Tracks riders can take, without depot runs.
     */
    public static function passenger(): self
    {
        return new self(...TrackKind::passenger());
    }
}
