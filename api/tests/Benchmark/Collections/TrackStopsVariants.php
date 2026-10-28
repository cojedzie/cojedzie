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

namespace App\Tests\Benchmark\Collections;

use App\Provider\ZtmGdansk\ZtmGdanskProvider;
use App\Service\IdUtils;
use App\Service\JsonStreamer;
use App\Utility\SequenceGenerator;
use Ds\Set;
use Kadet\Functional\Collections as c;
use Kadet\Functional\Collections\Lazy as lazy;

/**
 * Variants of the track stops step of the ZTM Gdańsk track import
 * (ZtmGdanskTrackDataImporter::getTrackStopsFromZtmApi()), written with each collection flavour.
 *
 * Every variant yields `string $trackId => iterable<array{stop_id: string, track_id: string, sequence: int}>`
 * and does exactly the same work, so they can be compared.
 */
final class TrackStopsVariants
{
    private const string FIXTURE          = __DIR__ . '/../../JsonStreamingTokenizer/stubs/ztm_stops_in_trip.json';
    private const string BRANCH           = '2022-03-07';
    private static ?array $decodedRecords = null;

    /**
     * Eager kadet/functional collections: every step builds a finished collection.
     */
    public static function materialized(iterable $records, Set $existingStopIds, IdUtils $ids): iterable
    {
        $all = $records
            |> c\groupBy(fn ($stop) => $ids->generate(
                ZtmGdanskProvider::IDENTIFIER,
                sprintf("R%sT%s", $stop['routeId'], $stop['tripId'])
            ));

        foreach ($all as $trackId => $stops) {
            $generator = new SequenceGenerator();

            yield $trackId => $stops
                |> c\map(fn ($stop) => [
                    'stop_id'  => $ids->generate(ZtmGdanskProvider::IDENTIFIER, $stop['stopId']),
                    'track_id' => $trackId,
                    'sequence' => $stop['stopSequence'],
                ])
                |> c\filter(fn ($stop) => $existingStopIds->contains($stop['stop_id']))
                |> c\sortBy(fn ($stop) => $stop['sequence'])
                |> c\map(fn ($stop) => [
                    ...$stop,
                    'sequence' => $generator->next(),
                ]);
        }
    }

    /**
     * Lazy kadet/functional collections wherever the step allows it: grouping and sorting need the whole input,
     * so they stay eager.
     */
    public static function lazy(iterable $records, Set $existingStopIds, IdUtils $ids): iterable
    {
        $all = $records
            |> c\groupBy(fn ($stop) => $ids->generate(
                ZtmGdanskProvider::IDENTIFIER,
                sprintf("R%sT%s", $stop['routeId'], $stop['tripId'])
            ));

        foreach ($all as $trackId => $stops) {
            $generator = new SequenceGenerator();

            yield $trackId => $stops
                |> lazy\map(fn ($stop) => [
                    'stop_id'  => $ids->generate(ZtmGdanskProvider::IDENTIFIER, $stop['stopId']),
                    'track_id' => $trackId,
                    'sequence' => $stop['stopSequence'],
                ])
                |> lazy\filter(fn ($stop) => $existingStopIds->contains($stop['stop_id']))
                |> c\sortBy(fn ($stop) => $stop['sequence'])
                |> lazy\map(fn ($stop) => [
                    ...$stop,
                    'sequence' => $generator->next(),
                ]);
        }
    }

    /**
     * Like materialized(), but each record is reduced to its ids and sequence before grouping, so the groups hold only
     * those instead of whole feed records. This is what the importer does.
     */
    public static function compact(iterable $records, Set $existingStopIds, IdUtils $ids): iterable
    {
        return self::compactGroups($records |> c\map(self::compactor($ids)), $existingStopIds);
    }

    /**
     * Like compact(), but the records are reduced lazily, so only the groups are built.
     */
    public static function compactLazy(iterable $records, Set $existingStopIds, IdUtils $ids): iterable
    {
        return self::compactGroups($records |> lazy\map(self::compactor($ids)), $existingStopIds);
    }

    /**
     * @return \Closure(array): array{track_id: string, stop_id: string, sequence: int}
     */
    private static function compactor(IdUtils $ids): \Closure
    {
        return fn (array $stop) => [
            'track_id' => $ids->generate(ZtmGdanskProvider::IDENTIFIER, sprintf("R%sT%s", $stop['routeId'], $stop['tripId'])),
            'stop_id'  => $ids->generate(ZtmGdanskProvider::IDENTIFIER, $stop['stopId']),
            'sequence' => $stop['stopSequence'],
        ];
    }

    private static function compactGroups(iterable $stops, Set $existingStopIds): iterable
    {
        foreach ($stops |> c\groupBy(fn (array $stop) => $stop['track_id']) as $trackId => $group) {
            $generator = new SequenceGenerator();

            yield $trackId => $group
                |> c\filter(fn (array $stop) => $existingStopIds->contains($stop['stop_id']))
                |> c\sortBy(fn (array $stop) => $stop['sequence'])
                |> c\map(fn (array $stop) => [
                    'stop_id'  => $stop['stop_id'],
                    'track_id' => $trackId,
                    'sequence' => $generator->next(),
                ]);
        }
    }

    /**
     * Reads every row of every track, forcing lazy results, and returns the number of rows.
     *
     * @phpstan-impure it consumes generators
     */
    public static function consume(iterable $tracks): int
    {
        $count = 0;

        foreach ($tracks as $rows) {
            foreach ($rows as $row) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Streams the stops in trips from the fixture, like the importer does.
     */
    public static function records(): iterable
    {
        return new JsonStreamer()->stream(self::FIXTURE, self::BRANCH . '.stopsInTrip');
    }

    /**
     * The same records as records(), decoded at once with json_decode() and cached.
     */
    public static function decodedRecords(): array
    {
        return self::$decodedRecords ??= self::decode();
    }

    /**
     * Ids of the stops in the fixture, except every stop with id divisible by 20, so the filter drops rows.
     *
     * Decodes the records without caching them, so building the set leaves no fixture in memory.
     */
    public static function existingStopIds(IdUtils $ids): Set
    {
        return new Set(
            self::decode()
                |> lazy\filter(fn ($record) => $record['stopId'] % 20 !== 0)
                |> lazy\map(fn ($record) => $ids->generate(ZtmGdanskProvider::IDENTIFIER, $record['stopId']))
        );
    }

    /**
     * Decodes only the branch's records: the whole 52 MB file doesn't fit in the memory limit once decoded.
     *
     * The branch is the first one in the file, and its records hold no arrays, so the list ends at the first `]`.
     */
    private static function decode(): array
    {
        $handle = fopen(self::FIXTURE, 'r');
        $head   = stream_get_line($handle, filesize(self::FIXTURE), ']');
        fclose($handle);

        $prefix = sprintf('{"%s":{', self::BRANCH);
        if (!str_starts_with($head, $prefix)) {
            throw new \UnexpectedValueException(sprintf('Expected the fixture to start with branch %s.', self::BRANCH));
        }

        return json_decode(
            substr($head, strpos($head, '"stopsInTrip":[') + \strlen('"stopsInTrip":')) . ']',
            true,
            flags: JSON_THROW_ON_ERROR
        );
    }
}
