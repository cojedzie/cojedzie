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

use Doctrine\DBAL\Exception\RetryableException;

final class Retry
{
    /**
     * Runs the attempt again when it fails on a deadlock or a lock wait timeout, after a random pause of 50-250 ms so
     * that the transactions that collided don't collide again. A rolled back transaction is retried as a whole.
     *
     * @template T
     *
     * @param callable(): T     $attempt
     * @param int               $attempts at most, the first one included
     * @param ?callable(): bool $canRetry whether another attempt can run at all, e.g. the entity manager is still open
     *
     * @return T
     *
     * @throws RetryableException when every attempt failed on a lock, or one did and it can't be retried
     */
    public static function onLockFailure(callable $attempt, int $attempts = 3, ?callable $canRetry = null): mixed
    {
        for ($made = 1; ; $made++) {
            try {
                return $attempt();
            } catch (RetryableException $exception) {
                if ($made >= $attempts || ($canRetry !== null && !$canRetry())) {
                    throw $exception;
                }

                usleep(random_int(50_000, 250_000));
            }
        }
    }
}
