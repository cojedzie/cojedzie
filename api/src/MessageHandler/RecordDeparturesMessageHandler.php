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

namespace App\MessageHandler;

use App\DepartureHistory\DepartureRecorder;
use App\Message\RecordDepartures;
use App\Schedule\ScheduleConfig;
use App\Utility\SentryCronMonitorTrait;
use Psr\Clock\ClockInterface;
use Sentry\MonitorConfig;
use Sentry\MonitorSchedule;
use Sentry\MonitorScheduleUnit;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class RecordDeparturesMessageHandler
{
    use SentryCronMonitorTrait;

    // Sentry cron monitor, alerts when recording stops
    private const string MONITOR          = 'record-departures';
    private ?\DateTimeImmutable $lastPoll = null;

    public function __construct(
        private readonly DepartureRecorder $recorder,
        private readonly ClockInterface $clock,
        private readonly ScheduleConfig $schedules,
    ) {
    }

    public function __invoke(RecordDepartures $message): void
    {
        if (!$this->recorder->isEnabled() || $this->isTooSoon()) {
            return;
        }

        // stamped at the start, so a slow poll doesn't make the next one look too soon
        $this->lastPoll = $this->now();

        if ($this->recorder->record()) {
            $this->heartbeat(self::MONITOR, new MonitorConfig(
                MonitorSchedule::interval(1, MonitorScheduleUnit::minute()),
                checkinMargin: 5,
                maxRuntime: 1,
            ), $this->now());
        }
    }

    /**
     * The scheduler yields every run missed while a poll was slow, right after it; one poll catches up with all of
     * them.
     */
    private function isTooSoon(): bool
    {
        if ($this->lastPoll === null) {
            return false;
        }

        $interval    = \DateInterval::createFromDateString($this->schedules->recordDepartures);
        $halfSeconds = (new \DateTimeImmutable('@0')->add($interval)->getTimestamp()) / 2;

        return $this->now()->getTimestamp() - $this->lastPoll->getTimestamp() < $halfSeconds;
    }

    private function now(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($this->clock->now());
    }
}
