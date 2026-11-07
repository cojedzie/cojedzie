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

namespace App\MessageHandler;

use App\DataImport\ImportGraph;
use App\Message\UpdateDataSources;
use App\Schedule\ScheduleConfig;
use App\Utility\SentryCronMonitorTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Sentry\CheckInStatus;
use Sentry\MonitorConfig;
use Sentry\MonitorSchedule;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Starts the import; workers run its tasks, and the one finishing it closes the check-in opened here.
 */
#[AsMessageHandler]
final class UpdateDataSourcesMessageHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use SentryCronMonitorTrait;

    // Sentry cron monitor, alerts when the daily update is missed or fails
    public const string MONITOR = 'update-data-sources';

    public function __construct(
        private readonly ImportGraph $graph,
        private readonly ScheduleConfig $schedules,
    ) {
    }

    public function __invoke(UpdateDataSources $message): void
    {
        $config = new MonitorConfig(
            MonitorSchedule::crontab($this->schedules->updateDataSources),
            checkinMargin: 30,
            // minutes; the first import downloads the whole schedule window
            maxRuntime: 240,
            timezone: date_default_timezone_get(),
        );

        $checkInId = $this->sentry->captureCheckIn(self::MONITOR, CheckInStatus::inProgress(), monitorConfig: $config);

        try {
            $this->graph->start($checkInId);
        } catch (\Throwable $exception) {
            $this->sentry->captureCheckIn(self::MONITOR, CheckInStatus::error(), checkInId: $checkInId);

            $this->logger->critical($exception->getMessage(), [
                'backtrace' => $exception->getTraceAsString(),
            ]);
        }
    }
}
