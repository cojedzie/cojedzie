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

namespace App\Provider\ZtmGdansk;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Upstream addresses of ZTM Gdańsk open data. Each is an environment variable, defaults are in
 * config/services.yaml; the dataset files get one each, as their resource ids change over time.
 */
final readonly class ZtmGdanskConfig
{
    private const int MAX_SCHEDULE_DAYS = 10;

    public function __construct(
        // TRISTAR API base: /departures, /displayMessages, /stopTimes
        #[Autowire(env: 'ZTM_GDANSK_API_URL')]
        public string $apiUrl,
        #[Autowire(env: 'ZTM_GDANSK_STOPS_URL')]
        public string $stopsUrl,
        // lines
        #[Autowire(env: 'ZTM_GDANSK_ROUTES_URL')]
        public string $routesUrl,
        // tracks
        #[Autowire(env: 'ZTM_GDANSK_TRIPS_URL')]
        public string $tripsUrl,
        #[Autowire(env: 'ZTM_GDANSK_STOPS_IN_TRIPS_URL')]
        public string $stopsInTripsUrl,
        // operators
        #[Autowire(env: 'ZTM_GDANSK_AGENCIES_URL')]
        public string $agenciesUrl,
        #[Autowire(env: 'ZTM_GDANSK_DISPLAYS_URL')]
        public string $displaysUrl,
        // how many days of schedules, today first, are kept; upstream publishes 10
        #[Autowire(env: 'int:ZTM_GDANSK_SCHEDULE_DAYS')]
        public int $scheduleDays,
    ) {
        if ($scheduleDays < 1 || $scheduleDays > self::MAX_SCHEDULE_DAYS) {
            throw new \InvalidArgumentException(sprintf(
                'ZTM_GDANSK_SCHEDULE_DAYS must be between 1 and %d, %d given.',
                self::MAX_SCHEDULE_DAYS,
                $scheduleDays,
            ));
        }
    }
}
