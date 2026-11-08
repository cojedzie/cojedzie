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

use OpenApi\Attributes as OA;

/**
 * Service days a timetable has trips for, both ends included.
 */
final readonly class TimetableRange implements Dto
{
    public function __construct(
        /**
         * First service day, as Y-m-d in the provider's timezone.
         */
        #[OA\Property(type: 'string', format: 'date', example: '2026-11-08')]
        private string $from,
        /**
         * Last service day, as Y-m-d in the provider's timezone.
         */
        #[OA\Property(type: 'string', format: 'date', example: '2026-11-22')]
        private string $to,
    ) {
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getTo(): string
    {
        return $this->to;
    }
}
