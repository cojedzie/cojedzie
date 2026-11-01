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

namespace App\Tests\DepartureHistory\Support;

use App\DepartureHistory\DepartureHistory;
use App\DepartureHistory\DepartureHistoryException;
use App\Provider\Live\LiveDeparture;

final class InMemoryDepartureHistory implements DepartureHistory
{
    /**
     * @var list<array{provider: string, observedAt: \DateTimeImmutable, departures: list<LiveDeparture>}>
     */
    public array $recorded = [];
    public int $prepared   = 0;

    // failures still to come from prepare()
    public int $failingPreparations = 0;
    public bool $failingRecords     = false;

    public function __construct(
        private readonly bool $enabled = true,
    ) {
    }

    #[\Override]
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    #[\Override]
    public function prepare(): void
    {
        if ($this->failingPreparations > 0) {
            $this->failingPreparations--;
            throw new DepartureHistoryException('Storage is down');
        }

        $this->prepared++;
    }

    #[\Override]
    public function record(string $provider, \DateTimeImmutable $observedAt, iterable $departures): void
    {
        if ($this->failingRecords) {
            throw new DepartureHistoryException('Storage is down');
        }

        $this->recorded[] = [
            'provider'   => $provider,
            'observedAt' => $observedAt,
            'departures' => [...$departures],
        ];
    }
}
