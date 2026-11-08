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
use App\DepartureHistory\ObservedDeparture;
use App\Provider\Live\LiveDeparture;
use Ds\Seq;
use Kadet\Functional\Collections as c;

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
    public bool $failingReads       = false;

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

    #[\Override]
    public function observed(string $provider, \DateTimeImmutable $from, \DateTimeImmutable $to, array $stopIds = [], array $lineIds = []): Seq
    {
        if ($this->failingReads) {
            throw new DepartureHistoryException('Storage is down');
        }

        $newest = [];
        foreach ($this->recorded as $record) {
            if ($record['provider'] !== $provider) {
                continue;
            }

            foreach ($record['departures'] as $departure) {
                if ($departure->scheduledAt < $from || $departure->scheduledAt >= $to) {
                    continue;
                }

                if (($stopIds !== [] && !in_array($departure->stopId, $stopIds, true)) || ($lineIds !== [] && !in_array($departure->lineId, $lineIds, true))) {
                    continue;
                }

                $key = implode('|', [$departure->stopId, $departure->lineId, $departure->tripKey, $departure->scheduledAt->getTimestamp()]);

                if (!isset($newest[$key]) || $newest[$key]->observedAt <= $record['observedAt']) {
                    $newest[$key] = new ObservedDeparture($departure, $record['observedAt']);
                }
            }
        }

        return array_values($newest) |> c\toSeq();
    }
}
