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

namespace App\Provider;

use Ds\Seq;
use Kadet\Functional\Collections as c;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Pairs planned departures with live ones (ADR 0030), whatever the live side is: departures of a realtime source or
 * rows of the departure history.
 */
#[Exclude] // static helpers
final class DeparturePairing
{
    /**
     * The key of a departure: its stop, line and scheduled minute, which is in UTC, so the same minute on another day
     * is another key and so are offsets of the same instant equal.
     */
    public static function key(string $stopId, string $lineId, \DateTimeInterface $scheduled): string
    {
        $minute = \DateTimeImmutable::createFromInterface($scheduled)
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d H:i');

        return sprintf('%s::%s::%s', $stopId, $lineId, $minute);
    }

    /**
     * Full outer join by key: planned entries in their order, then live ones that matched none, in their order. When
     * several live entries share a key, the first one wins and the rest are dropped.
     *
     * @template P
     * @template L
     *
     * @param iterable<P>        $planned
     * @param iterable<L>        $live
     * @param callable(P): string $plannedKey
     * @param callable(L): string $liveKey
     *
     * @return Seq<array{planned: ?P, live: ?L}>
     */
    public static function pair(iterable $planned, iterable $live, callable $plannedKey, callable $liveKey): Seq
    {
        // the first live entry of each key, a new map the planned entries take their matches out of
        $remaining = $live
            |> c\groupBy($liveKey)
            |> c\map(fn (Seq $entries) => $entries->first());

        $pairs = $planned
            |> c\map(fn ($entry) => [
                'planned' => $entry,
                'live'    => $remaining->remove($plannedKey($entry), null),
            ]);

        // read only after every planned entry took its match out
        $unmatched = $remaining
            |> c\map(fn ($entry) => [
                'planned' => null,
                'live'    => $entry,
            ]);

        return $pairs
            |> c\concat($unmatched)
            |> c\toSeq();
    }
}
