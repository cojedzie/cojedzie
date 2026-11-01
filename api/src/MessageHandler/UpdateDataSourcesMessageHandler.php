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

use App\Message\UpdateDataSources;
use App\Schedule\ScheduleConfig;
use App\Service\DataUpdater;
use App\Utility\SentryCronMonitorTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Sentry\MonitorConfig;
use Sentry\MonitorSchedule;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateDataSourcesMessageHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use SentryCronMonitorTrait;

    // Sentry cron monitor, alerts when the daily update is missed or fails
    private const string MONITOR = 'update-data-sources';

    public function __construct(
        private readonly DataUpdater $updater,
        private readonly ScheduleConfig $schedules,
    ) {
    }

    public function __invoke(UpdateDataSources $message)
    {
        $config = new MonitorConfig(
            MonitorSchedule::crontab($this->schedules->updateDataSources),
            checkinMargin: 30,
            maxRuntime: 120,
            timezone: date_default_timezone_get(),
        );

        try {
            $this->monitored(self::MONITOR, $config, $this->updater->update(...));
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage(), [
                'backtrace' => $exception->getTraceAsString(),
            ]);
        }
    }
}
