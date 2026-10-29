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

namespace App\Schedule;

use App\Message\CheckFederatedConnection;
use App\Message\CleanupFederatedConnections;
use App\Repository\FederatedConnectionEntityRepository;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Component\Scheduler\Trigger\CallbackMessageProvider;

#[AsSchedule('hub')]
class HubScheduleProvider implements ScheduleProviderInterface
{
    private Schedule $schedule;

    public function __construct(
        private readonly FederatedConnectionEntityRepository $federatedConnectionEntityRepository,
        private readonly ScheduleConfig $config,
    ) {
    }

    #[\Override]
    public function getSchedule(): Schedule
    {
        return $this->schedule ??= new Schedule()
            ->with(
                RecurringMessage::cron(
                    $this->config->federationCheck,
                    new CallbackMessageProvider($this->generateCheckConnectionMessages(...), 'federation:check')
                ),
                RecurringMessage::cron($this->config->federationCleanup, new CleanupFederatedConnections())
            );
    }

    private function generateCheckConnectionMessages()
    {
        foreach ($this->federatedConnectionEntityRepository->findAllConnectionsToCheck() as $connection) {
            yield new CheckFederatedConnection(connectionId: $connection->getId());
        }
    }
}
