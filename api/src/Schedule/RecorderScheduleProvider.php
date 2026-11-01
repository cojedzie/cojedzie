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

namespace App\Schedule;

use App\DepartureHistory\DepartureHistory;
use App\Message\RecordDepartures;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

/**
 * Recording of departure history, on its own transport (scheduler_recorder) and handled there: on main, the daily
 * import would hold it up for minutes. Empty without ClickHouse, so consuming the transport is harmless.
 */
#[AsSchedule('recorder')]
class RecorderScheduleProvider implements ScheduleProviderInterface
{
    private Schedule $schedule;

    public function __construct(
        private readonly DepartureHistory $history,
        private readonly ScheduleConfig $config,
    ) {
    }

    #[\Override]
    public function getSchedule(): Schedule
    {
        if (isset($this->schedule)) {
            return $this->schedule;
        }

        $this->schedule = new Schedule();

        if ($this->history->isEnabled()) {
            $this->schedule->add(RecurringMessage::every($this->config->recordDepartures, new RecordDepartures()));
        }

        return $this->schedule;
    }
}
