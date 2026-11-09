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

namespace App\Subscriber;

use App\DataImport\ImportStatus;
use App\Entity\ImportEntity;
use App\Event\ImportFinishedEvent;
use App\Event\ImportStartedEvent;
use App\Schedule\ScheduleConfig;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Sentry\CheckInStatus;
use Sentry\MonitorConfig;
use Sentry\MonitorSchedule;
use Sentry\State\HubInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Reports imports to the Sentry cron monitor, which alerts when the daily import is missed or fails. The check-in
 * opened when an import starts is kept by import id until a worker ends the import, usually another one.
 */
final readonly class ImportCronMonitorSubscriber
{
    public const string MONITOR = 'update-data-sources';

    public function __construct(
        private HubInterface $sentry,
        private ScheduleConfig $schedules,
        #[Autowire(service: 'cache.import_check_ins')]
        private CacheItemPoolInterface $checkIns,
    ) {
    }

    #[AsEventListener]
    public function onImportStarted(ImportStartedEvent $event): void
    {
        $checkInId = $this->sentry->captureCheckIn(
            self::MONITOR,
            CheckInStatus::inProgress(),
            monitorConfig: $this->monitorConfig(),
        );

        // without a Sentry client there is no check-in
        if ($checkInId === null) {
            return;
        }

        $this->checkIns->save($this->checkInOf($event->import)->set($checkInId));
    }

    #[AsEventListener]
    public function onImportFinished(ImportFinishedEvent $event): void
    {
        $checkIn = $this->checkInOf($event->import);

        if (!$checkIn->isHit()) {
            return;
        }

        $this->sentry->captureCheckIn(
            self::MONITOR,
            $event->import->getStatus() === ImportStatus::Succeeded ? CheckInStatus::ok() : CheckInStatus::error(),
            checkInId: $checkIn->get(),
        );

        $this->checkIns->deleteItem($checkIn->getKey());
    }

    private function checkInOf(ImportEntity $import): CacheItemInterface
    {
        return $this->checkIns->getItem($import->getId()->toRfc4122());
    }

    private function monitorConfig(): MonitorConfig
    {
        return new MonitorConfig(
            MonitorSchedule::crontab($this->schedules->updateDataSources),
            checkinMargin: 30,
            // minutes; the first import downloads the whole schedule window
            maxRuntime: 240,
            timezone: date_default_timezone_get(),
        );
    }
}
