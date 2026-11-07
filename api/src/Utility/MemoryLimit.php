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

final class MemoryLimit
{
    /**
     * Runs the job with `memory_limit` raised to the limit, and restores the previous one afterwards: a long-lived
     * process (a messenger worker) keeps going after the job, so the raised limit must not outlive it.
     *
     * @template T
     *
     * @param string        $limit as in php.ini, e.g. "2G"
     * @param callable(): T $job
     *
     * @return T
     */
    public static function raisedWhile(string $limit, callable $job): mixed
    {
        $previous = ini_set('memory_limit', $limit);

        try {
            return $job();
        } finally {
            self::restore((string) $previous);
        }
    }

    /**
     * PHP refuses a limit below the memory in use; the worker's own --memory-limit then stops it instead.
     */
    private static function restore(string $previous): void
    {
        $bytes = ini_parse_quantity($previous);

        if ($bytes === -1 || memory_get_usage(true) < $bytes) {
            ini_set('memory_limit', $previous);
        }
    }
}
