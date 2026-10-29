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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Sentry\CheckInStatus;
use Sentry\MonitorConfig;
use Sentry\MonitorSchedule;
use Sentry\State\HubInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateDataSourcesMessageHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    // Sentry cron monitor, alerts when the daily update is missed or fails
    private const string MONITOR = 'update-data-sources';

    public function __construct(
        private readonly DataUpdater $updater,
        private readonly HubInterface $hub,
        private readonly ScheduleConfig $schedules,
    ) {
    }

    public function __invoke(UpdateDataSources $message)
    {
        $checkInId = $this->hub->captureCheckIn(
            self::MONITOR,
            CheckInStatus::inProgress(),
            monitorConfig: new MonitorConfig(
                MonitorSchedule::crontab($this->schedules->updateDataSources),
                checkinMargin: 30,
                maxRuntime: 120,
                timezone: date_default_timezone_get(),
            ),
        );

        try {
            $this->updater->update();
            $status = CheckInStatus::ok();
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage(), [
                'backtrace' => $exception->getTraceAsString(),
            ]);
            $status = CheckInStatus::error();
        }

        $this->hub->captureCheckIn(self::MONITOR, $status, checkInId: $checkInId);
    }
}
