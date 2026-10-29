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

namespace App\Schedule;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * When the scheduled jobs run, as cron expressions in the server's time zone. Each is an environment variable,
 * defaults are in config/services.yaml.
 */
final readonly class ScheduleConfig
{
    public function __construct(
        // import of every provider's data, see DefaultScheduleProvider
        #[Autowire(env: 'SCHEDULE_UPDATE_DATA_SOURCES')]
        public string $updateDataSources,
        // health checks of federated connections, on the hub, see HubScheduleProvider
        #[Autowire(env: 'SCHEDULE_FEDERATION_CHECK')]
        public string $federationCheck,
        // removal of closed federated connections, on the hub
        #[Autowire(env: 'SCHEDULE_FEDERATION_CLEANUP')]
        public string $federationCleanup,
    ) {
    }
}
