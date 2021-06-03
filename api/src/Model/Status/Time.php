<?php
/*
 * Copyright (C) 2021 Kacper Donat
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

namespace App\Model\Status;

use App\Model\DTO;
use Carbon\Carbon;
use JMS\Serializer\Annotation as Serializer;
use OpenApi\Annotations as OA;
use function App\Functions\setup;

class Time implements DTO
{
    /**
     * Current date and time on node.
     *
     * @Serializer\Type("Carbon")
     * @OA\Property(type="string", format="date-time")
     */
    private Carbon $current;

    /**
     * Timezone for this node.
     *
     * @Serializer\Type("string")
     * @OA\Property(type="string", format="timezone", example="Europe/Warsaw")
     */
    private string $timezone;

    public static function createFromDateTime(Carbon $now)
    {
        return setup(new static, function (Time $time) use ($now) {
            $time->setCurrent($now);
            $time->setTimezone($now->timezoneName);
        });
    }

    public function getCurrent(): Carbon
    {
        return $this->current;
    }

    public function setCurrent(Carbon $current): void
    {
        $this->current = $current;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): void
    {
        $this->timezone = $timezone;
    }
}
