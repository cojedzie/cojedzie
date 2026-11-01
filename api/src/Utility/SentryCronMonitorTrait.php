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

namespace App\Utility;

use Sentry\CheckInStatus;
use Sentry\MonitorConfig;
use Sentry\State\HubInterface;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Check-ins of Sentry cron monitors, which alert when a scheduled job stops running or fails.
 */
trait SentryCronMonitorTrait
{
    private HubInterface $sentry;

    /**
     * @var array<string, int> unix time of the last heartbeat, by monitor
     */
    private array $heartbeats = [];

    #[Required]
    public function setSentryHub(
        HubInterface $sentry
    ): void {
        $this->sentry = $sentry;
    }

    /**
     * Runs a job between an in-progress check-in and an ok or error one.
     *
     * @template T
     *
     * @param callable(): T $job
     *
     * @return T
     */
    protected function monitored(string $monitor, MonitorConfig $config, callable $job): mixed
    {
        $checkInId = $this->sentry->captureCheckIn($monitor, CheckInStatus::inProgress(), monitorConfig: $config);

        try {
            $result = $job();
        } catch (\Throwable $exception) {
            $this->sentry->captureCheckIn($monitor, CheckInStatus::error(), checkInId: $checkInId);

            throw $exception;
        }

        $this->sentry->captureCheckIn($monitor, CheckInStatus::ok(), checkInId: $checkInId);

        return $result;
    }

    /**
     * An ok check-in at most once per interval, for jobs that run more often than a monitor can be scheduled.
     */
    protected function heartbeat(string $monitor, MonitorConfig $config, \DateTimeInterface $now, int $intervalSeconds = 60): void
    {
        $last = $this->heartbeats[$monitor] ?? null;
        if ($last !== null && $now->getTimestamp() - $last < $intervalSeconds) {
            return;
        }

        $this->heartbeats[$monitor] = $now->getTimestamp();
        $this->sentry->captureCheckIn($monitor, CheckInStatus::ok(), monitorConfig: $config);
    }
}
