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

use App\Provider\Live\DepartureStatus;
use App\Provider\Live\LiveDeparture;
use App\Provider\Live\LiveDepartureException;
use App\Provider\Live\LiveDepartureSource;
use Ds\Seq;

final readonly class FakeLiveDepartureSource implements LiveDepartureSource
{
    /**
     * @param \Closure(): Seq<LiveDeparture> $all
     */
    private function __construct(
        private string $provider,
        private \Closure $all,
    ) {
    }

    public static function with(string $provider, LiveDeparture ...$departures): self
    {
        return new self($provider, fn () => new Seq($departures));
    }

    public static function failing(string $provider): self
    {
        return new self($provider, fn () => throw new LiveDepartureException(sprintf('%s is down', $provider)));
    }

    /**
     * @param \Closure(): Seq<LiveDeparture> $all
     */
    public static function using(string $provider, \Closure $all): self
    {
        return new self($provider, $all);
    }

    public static function departure(string $stop = '8227'): LiveDeparture
    {
        return new LiveDeparture(
            stopId: $stop,
            lineId: '171',
            lineSymbol: '171',
            tripKey: '12@2026-11-01T11:08:00Z',
            scheduledAt: new \DateTimeImmutable('2026-11-01T11:13:00Z'),
            estimatedAt: new \DateTimeImmutable('2026-11-01T11:12:03Z'),
            status: DepartureStatus::Realtime,
            headsign: 'Oliwa',
            vehicle: '9221',
            duty: '171-01',
        );
    }

    #[\Override]
    public function getProviderIdentifier(): string
    {
        return $this->provider;
    }

    #[\Override]
    public function all(): Seq
    {
        return ($this->all)();
    }
}
