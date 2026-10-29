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

namespace App\Utility;

/**
 * Environment variables controlling the app are namespaced: COJEDZIE_, or a subsystem's own prefix such as
 * FEDERATION_ or SENTRY_ (ADR 0011). Their previous names still work:
 * a variable set only under its old name is copied to the new one, with a deprecation notice.
 */
final class Environment
{
    /**
     * Previous names of renamed variables, by new name.
     */
    public const array LEGACY_NAMES = [
        'COJEDZIE_EVENT_QUEUE'                   => 'APP_EVENT_QUEUE',
        'COJEDZIE_SCHEDULE_UPDATE_DATA_SOURCES'  => 'SCHEDULE_UPDATE_DATA_SOURCES',
        'COJEDZIE_SCHEDULE_FEDERATION_CHECK'     => 'SCHEDULE_FEDERATION_CHECK',
        'COJEDZIE_SCHEDULE_FEDERATION_CLEANUP'   => 'SCHEDULE_FEDERATION_CLEANUP',
        'COJEDZIE_SCHEDULE_COLLECT_STALE_DATA'   => 'SCHEDULE_COLLECT_STALE_DATA',
        'COJEDZIE_STALE_DATA_RETENTION'          => 'STALE_DATA_RETENTION',
    ];

    private function __construct()
    {
    }

    /**
     * Copies variables set only under their previous name to their new name.
     *
     * @param array<string, mixed> $variables e.g. $_SERVER or $_ENV
     *
     * @return array<string, mixed>
     */
    public static function withLegacyNames(array $variables): array
    {
        foreach (self::LEGACY_NAMES as $name => $legacy) {
            if (!array_key_exists($name, $variables) && array_key_exists($legacy, $variables)) {
                trigger_deprecation('cojedzie/api', '2026.10', 'The %s environment variable is deprecated, use %s.', $legacy, $name);

                $variables[$name] = $variables[$legacy];
            }
        }

        return $variables;
    }
}
