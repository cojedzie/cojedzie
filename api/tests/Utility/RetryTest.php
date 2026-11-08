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

namespace App\Tests\Utility;

use App\Utility\Retry;
use Doctrine\DBAL\Driver\PDO\Exception as PdoDriverException;
use Doctrine\DBAL\Exception\DeadlockException;
use PHPUnit\Framework\TestCase;

final class RetryTest extends TestCase
{
    private int $attempts = 0;

    public function testAttemptFailingOnALockIsRetriedUntilItSucceeds(): void
    {
        $result = Retry::onLockFailure($this->deadlockingFirst(2));

        $this->assertSame('done', $result);
        $this->assertSame(3, $this->attempts);
    }

    public function testLockFailureOfTheLastAttemptIsThrown(): void
    {
        try {
            Retry::onLockFailure($this->deadlockingFirst(3));
            $this->fail('The deadlock was not rethrown.');
        } catch (DeadlockException) {
        }

        $this->assertSame(3, $this->attempts);
    }

    public function testNothingIsRetriedWhenTheCallerCannot(): void
    {
        $this->expectException(DeadlockException::class);

        try {
            Retry::onLockFailure($this->deadlockingFirst(1), canRetry: fn () => false);
        } finally {
            $this->assertSame(1, $this->attempts);
        }
    }

    public function testOtherFailuresAreNotRetried(): void
    {
        $this->expectExceptionMessageIsOrContains('Feed is unavailable');

        try {
            Retry::onLockFailure(function (): never {
                $this->attempts++;

                throw new \RuntimeException('Feed is unavailable');
            });
        } finally {
            $this->assertSame(1, $this->attempts);
        }
    }

    /**
     * @return \Closure(): string an attempt deadlocking the given number of times, then done
     */
    private function deadlockingFirst(int $deadlocks): \Closure
    {
        return function () use ($deadlocks): string {
            if (++$this->attempts <= $deadlocks) {
                throw new DeadlockException(PdoDriverException::new(new \PDOException('Deadlock found when trying to get lock')), null);
            }

            return 'done';
        };
    }
}
