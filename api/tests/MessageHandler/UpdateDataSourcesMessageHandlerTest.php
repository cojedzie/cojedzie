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

namespace App\Tests\MessageHandler;

use App\Message\UpdateDataSources;
use App\MessageHandler\UpdateDataSourcesMessageHandler;
use App\Schedule\ScheduleConfig;
use App\Service\DataUpdater;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Sentry\CheckInStatus;
use Sentry\MonitorConfig;
use Sentry\State\HubInterface;

final class UpdateDataSourcesMessageHandlerTest extends TestCase
{
    /**
     * @var list<array{string, CheckInStatus, ?MonitorConfig, ?string}>
     */
    private array $checkIns = [];

    public function testReportsSuccessfulUpdate(): void
    {
        $this->handle($this->createStub(DataUpdater::class));

        $this->assertCheckIns(CheckInStatus::ok());
    }

    public function testReportsFailedUpdate(): void
    {
        $updater = $this->createStub(DataUpdater::class);
        $updater->method('update')->willThrowException(new \RuntimeException('Source unavailable'));

        $this->handle($updater);

        $this->assertCheckIns(CheckInStatus::error());
    }

    private function handle(DataUpdater $updater): void
    {
        $hub = $this->createStub(HubInterface::class);
        $hub->method('captureCheckIn')->willReturnCallback(
            function (string $slug, CheckInStatus $status, $duration = null, ?MonitorConfig $config = null, ?string $id = null): string {
                $this->checkIns[] = [$slug, $status, $config, $id];

                return 'check-in-id';
            }
        );

        $handler = new UpdateDataSourcesMessageHandler($updater, new ScheduleConfig(
            updateDataSources: '30 2 * * *',
            federationCheck: '* * * * *',
            federationCleanup: '0 0 * * *',
            collectStaleData: '0 6 * * *',
        ));
        $handler->setSentryHub($hub);
        $handler->setLogger(new NullLogger());
        $handler(new UpdateDataSources());
    }

    private function assertCheckIns(CheckInStatus $result): void
    {
        $this->assertCount(2, $this->checkIns);

        [[$slug, $status, $config], [$finishedSlug, $finishedStatus, , $finishedId]] = $this->checkIns;

        $this->assertSame('update-data-sources', $slug);
        $this->assertSame(CheckInStatus::inProgress(), $status);
        $this->assertSame('30 2 * * *', $config?->getSchedule()->getValue());

        $this->assertSame('update-data-sources', $finishedSlug);
        $this->assertSame($result, $finishedStatus);
        $this->assertSame('check-in-id', $finishedId);
    }
}
