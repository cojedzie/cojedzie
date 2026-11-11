<?php
/*
 * Copyright (C) 2021 Kacper Donat
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

namespace App\Provider\ZtmGdansk\DataImporter;

use App\DataImport\MilestoneType;
use App\DataImport\ProgressReporterInterface;
use App\DataImport\TableUpserter;
use App\Dto\TrackKind;
use App\Event\DataUpdateEvent;
use App\Provider\ZtmGdansk\ZtmGdanskConfig;
use App\Provider\ZtmGdansk\ZtmGdanskProvider;
use App\Service\AbstractDataImporter;
use App\Service\IdUtils;
use App\Service\JsonStreamer;
use App\Utility\SequenceGenerator;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Ds\Set;
use Kadet\Functional\Collections as c;
use Psr\Clock\ClockInterface;

/**
 * Imports the tracks of every day of the schedule window, versioned by the topology version of their stops in trips.
 *
 * A track is valid from the start of the first day listing it (kept from an earlier import) to the end of the last
 * one, so a superseded version stops being extended and expires on its own. ZTM labels every published day with an
 * agency's current topology version, so a version superseded for the whole window expires at once. Its stops share
 * its validity.
 */
class ZtmGdanskTrackDataImporter extends AbstractDataImporter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly TableUpserter $upserter,
        private readonly IdUtils $idUtils,
        private readonly JsonStreamer $jsonStreamer,
        private readonly ZtmGdanskConfig $config,
        private readonly ClockInterface $clock,
    ) {
    }

    #[\Override]
    public function import(ProgressReporterInterface $reporter, DataUpdateEvent $event): void
    {
        $this->connection->transactional(fn () => $this->importTracks($reporter->subtask('Import tracks'), $event));
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            ZtmGdanskStopDataImporter::class,
            ZtmGdanskLineDataImporter::class,
        ];
    }

    private function importTracks(ProgressReporterInterface $reporter, DataUpdateEvent $event): void
    {
        $dates      = ZtmGdanskProvider::feedDates($this->clock, $this->config->scheduleDays);
        $knownStops = $this->stopsOf($event);

        // read before the stops in trips, so no stream is left idle while a day of tracks is written
        $trips = $this->tripsByDate($dates);

        /** @var array<string, array{string, string}> $listings first and last date listing each track */
        $listings = [];
        $total    = 0;
        $day      = 0;

        $days = $this->jsonStreamer->streamDated($this->config->stopsInTripsUrl, 'stopsInTrip', $dates);
        foreach ($days as $date => $records) {
            $tracks = $this->groupTrackStops($records, $knownStops);
            $result = $this->importTracksOfDay($date, $tracks, $trips[$date] ?? [], $listings, $event);
            unset($trips[$date]);

            $total += $result['imported'];
            $this->reportDay($reporter, ++$day, count($dates), $date, $result);
        }

        $this->writeValidity($listings, $event);
        $this->expireSupersededVersions(array_keys($listings));

        $reporter->progress(
            count($dates),
            max: count($dates),
            comment: sprintf('OK, %d tracks', $total),
            finished: true,
        );
    }

    /**
     * Writes the tracks first listed on this day and extends the listing of those listed on an earlier one.
     *
     * @param iterable<string, array{string, iterable<array>}>             $tracks   trip id and stops, by track id
     * @param array<string, array{line_id: string, description: string, kind: string}> $trips    trips of the day, by id
     * @param array<string, array{string, string}>                        $listings first and last date listing each track, updated
     *
     * @return array{imported: int, skipped: int} tracks written, and tracks without a trip in the trips feed
     */
    private function importTracksOfDay(
        string $date,
        iterable $tracks,
        array $trips,
        array &$listings,
        DataUpdateEvent $event,
    ): array {
        $imported = 0;
        $skipped  = 0;

        foreach ($tracks as $trackId => [$tripId, $stops]) {
            if (isset($listings[$trackId])) {
                // a track listed on several days is written once, from the first day listing it
                $listings[$trackId] = self::extendListing($listings[$trackId], $date);
            } elseif (!isset($trips[$tripId])) {
                // without its trip the track has no line
                $skipped++;
            } else {
                $this->writeTrack($trackId, $trips[$tripId], $stops, $event);
                $listings[$trackId] = [$date, $date];
                $imported++;
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    /**
     * @param array{string, string} $listing first and last date listing the track
     *
     * @return array{string, string}
     */
    private static function extendListing(array $listing, string $date): array
    {
        [$first, $last] = $listing;

        return [min($first, $date), max($last, $date)];
    }

    /**
     * @param array{imported: int, skipped: int} $result
     */
    private function reportDay(
        ProgressReporterInterface $reporter,
        int $day,
        int $days,
        string $date,
        array $result,
    ): void {
        $reporter->progress($day, max: $days, comment: sprintf('%s: %d tracks', $date, $result['imported']));

        if ($result['skipped'] > 0) {
            $reporter->milestone(
                sprintf('Skipped %d tracks of %s without a trip in the trips feed', $result['skipped'], $date),
                MilestoneType::Warning,
            );
        }
    }

    /**
     * Stops written by this import, the only ones a track may go through.
     */
    private function stopsOf(DataUpdateEvent $event): Set
    {
        return new Set(
            $this->connection->createQueryBuilder()
                ->from('stop', 's')
                ->select('id')
                ->where('s.provider_id = :provider_id')
                ->andWhere('s.import_id = :import_id')
                ->setParameter('provider_id', ZtmGdanskProvider::IDENTIFIER)
                ->setParameter('import_id', $event->import->getId(), 'uuid')
                ->executeQuery()
                ->iterateColumn()
        );
    }

    /**
     * Trips of the window's days, by date and their R{routeId}T{tripId} id, reduced to what a track needs.
     *
     * @param list<string> $dates
     *
     * @return array<string, array<string, array{line_id: string, description: string, kind: string}>>
     */
    private function tripsByDate(array $dates): array
    {
        $result = [];
        foreach ($this->jsonStreamer->streamDated($this->config->tripsUrl, 'trips', $dates) as $date => $trips) {
            $result[$date] = $this->tripsById($trips);
        }

        return $result;
    }

    /**
     * @param iterable<array> $trips records of the trips feed
     *
     * @return array<string, array{line_id: string, description: string, kind: string}>
     */
    private function tripsById(iterable $trips): array
    {
        $result = [];
        foreach ($trips as $trip) {
            $result[$trip['id']] = [
                'line_id'     => $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $trip['routeId']),
                // without the number ZTM appends to some headsigns, e.g. "Oliwa (123)"
                'description' => preg_replace('/\(\d+\)/', '', (string) $trip['tripHeadsign']),
                'kind'        => self::kind($trip['type'] ?? null)->value,
            ];
        }

        return $result;
    }

    /**
     * Kind of the track of a trip, from its type: MAIN, SIDE or NON_PASSENGER. Agencies other than ZTM Gdańsk send
     * UNKNOWN, so their tracks are side ones, as are tracks of a type added later.
     */
    private static function kind(?string $type): TrackKind
    {
        return match ($type) {
            'MAIN'          => TrackKind::Main,
            'NON_PASSENGER' => TrackKind::NonPassenger,
            default         => TrackKind::Side,
        };
    }

    /**
     * @param array{line_id: string, description: string, kind: string}                $trip
     * @param iterable<array{stop_id: string, track_id: string, sequence: int}> $stops
     */
    private function writeTrack(string $trackId, array $trip, iterable $stops, DataUpdateEvent $event): void
    {
        $this->upserter->upsert(
            table: 'track',
            rows: [$trackId => $trip],
            providerId: ZtmGdanskProvider::IDENTIFIER,
            import: $event->import,
        );

        $this->replaceTrackStops($trackId, $stops, $event);
    }

    /**
     * Replaces the stops of the track and sets its origin to the first one and its final stop to the last one.
     *
     * @param iterable<array{stop_id: string, track_id: string, sequence: int}> $stops
     */
    private function replaceTrackStops(string $trackId, iterable $stops, DataUpdateEvent $event): void
    {
        $this->connection->delete('track_stop', ['track_id' => $trackId]);

        $origin = null;
        $final  = null;
        foreach ($stops as $stop) {
            $final = $this->insertTrackStop($stop, $event);
            $origin ??= $final;
        }

        // a track whose stops are all unknown has neither an origin nor a final stop
        $this->connection->update('track', ['origin_id' => $origin, 'final_id' => $final], ['id' => $trackId]);
    }

    /**
     * @param array{stop_id: string, track_id: string, sequence: int} $stop
     *
     * @return int|string id of the inserted track stop
     */
    private function insertTrackStop(array $stop, DataUpdateEvent $event): int|string
    {
        $this->connection->insert(
            'track_stop',
            [...$stop, 'import_id' => $event->import->getId()],
            ['import_id' => 'uuid'],
        );

        return $this->connection->lastInsertId();
    }

    /**
     * Sets the validity of the tracks to the days listing them, keeping the start of tracks imported before, and
     * gives their stops the same validity.
     *
     * @param array<string, array{string, string}> $listings first and last date listing each track
     */
    private function writeValidity(array $listings, DataUpdateEvent $event): void
    {
        foreach ($listings as $trackId => [$first, $last]) {
            $this->writeTrackValidity($trackId, $first, $last);
        }

        $this->copyTrackValidityToStops($event);
    }

    private function writeTrackValidity(string $trackId, string $firstDate, string $lastDate): void
    {
        $timezone = new \DateTimeZone(ZtmGdanskProvider::TIMEZONE);

        $this->connection->executeStatement(
            'UPDATE track SET valid_from = COALESCE(valid_from, :valid_from), valid_to = :valid_to WHERE id = :id',
            [
                'valid_from' => new \DateTimeImmutable($firstDate, $timezone),
                'valid_to'   => new \DateTimeImmutable($lastDate . ' 23:59:59', $timezone),
                'id'         => $trackId,
            ],
            [
                'valid_from' => 'datetime',
                'valid_to'   => 'datetime',
            ],
        );
    }

    private function copyTrackValidityToStops(DataUpdateEvent $event): void
    {
        $this->connection->executeStatement(
            'UPDATE track_stop SET
                valid_from = (SELECT t.valid_from FROM track t WHERE t.id = track_stop.track_id),
                valid_to = (SELECT t.valid_to FROM track t WHERE t.id = track_stop.track_id)
            WHERE import_id = :import_id',
            ['import_id' => $event->import->getId()],
            ['import_id' => 'uuid'],
        );
    }

    /**
     * Ends now the validity of other versions of the listed tracks' routes and trips that no day of the window lists
     * anymore. Only versions of what was listed are touched, so a partial feed doesn't expire unrelated tracks.
     *
     * @param list<string> $listed ids of the tracks listed by this import
     */
    private function expireSupersededVersions(array $listed): void
    {
        $now = \DateTimeImmutable::createFromInterface($this->clock->now())->setTimezone(new \DateTimeZone('UTC'));

        // in chunks, an UPDATE ... IN (...) of every superseded track could exceed the limits of the database
        foreach (array_chunk($this->supersededVersions($listed, $now), length: 1000) as $tracks) {
            $this->expire('track', 'id', $tracks, $now);
            $this->expire('track_stop', 'track_id', $tracks, $now);
        }
    }

    /**
     * Tracks still valid that weren't listed, but share their route and trip with a listed one.
     *
     * @param list<string> $listed ids of the tracks listed by this import
     *
     * @return list<string>
     */
    private function supersededVersions(array $listed, \DateTimeImmutable $now): array
    {
        $listed       = new Set($listed);
        $listedTrips  = new Set(array_map($this->withoutVersion(...), $listed->toArray()));
        $isSuperseded = fn (string $id) => !$listed->contains($id)
            && $listedTrips->contains($this->withoutVersion($id) ?? $id);

        return array_values(array_filter($this->tracksValidAfter($now), $isSuperseded));
    }

    /**
     * @return list<string>
     */
    private function tracksValidAfter(\DateTimeImmutable $now): array
    {
        return $this->connection->fetchFirstColumn(
            'SELECT id FROM track WHERE provider_id = :provider_id AND valid_to > :now',
            ['provider_id' => ZtmGdanskProvider::IDENTIFIER, 'now' => $now],
            ['now' => 'datetime_immutable'],
        );
    }

    /**
     * Ends now the validity of the rows of the tracks, unless it ends sooner.
     *
     * @param list<string> $tracks
     */
    private function expire(string $table, string $trackColumn, array $tracks, \DateTimeImmutable $now): void
    {
        $this->connection->executeStatement(
            sprintf('UPDATE %s SET valid_to = :now WHERE %s IN (:ids) AND valid_to > :now', $table, $trackColumn),
            ['now' => $now, 'ids' => $tracks],
            ['now' => 'datetime_immutable', 'ids' => ArrayParameterType::STRING],
        );
    }

    /**
     * The stored id of a track up to its topology version, provider::R{routeId}T{tripId}V, or null for a track
     * without a version (imported before tracks had validity).
     */
    private function withoutVersion(string $trackId): ?string
    {
        return preg_match('/^(.*V)\d+$/', $trackId, $matches) ? $matches[1] : null;
    }

    /**
     * Groups the stops in trips by track, keeps the stops that exist and numbers them in order of their sequence.
     *
     * The feed isn't ordered by track, so the groups are built in memory. Each record is reduced to its ids and
     * sequence first, so the groups don't hold whole feed records. Each track comes with the id of its trip in the
     * trips feed.
     *
     * @param iterable<array> $records
     *
     * @return iterable<string, array{string, iterable<array{stop_id: string, track_id: string, sequence: int}>}>
     */
    private function groupTrackStops(iterable $records, Set $existingStopIds): iterable
    {
        $tracks = $records
            |> c\map($this->idsOfStopInTrip(...))
            |> c\groupBy(fn (array $stop) => $stop['track_id']);

        // a track whose stops are all unknown is still yielded, so its stops from an earlier import are removed
        foreach ($tracks as $trackId => $stops) {
            $firstStop = $stops |> c\first();

            yield $trackId => [
                $firstStop['trip_id'],
                $this->numberedStops($trackId, $stops, $existingStopIds),
            ];
        }
    }

    /**
     * @param array<string, mixed> $record of the stops in trips feed
     *
     * @return array{track_id: string, trip_id: string, stop_id: string, sequence: int}
     */
    private function idsOfStopInTrip(array $record): array
    {
        return [
            'track_id' => $this->idUtils->generate(
                ZtmGdanskProvider::IDENTIFIER,
                sprintf('R%sT%sV%s', $record['routeId'], $record['tripId'], $record['topologyVersionId'])
            ),
            'trip_id'  => sprintf('R%sT%s', $record['routeId'], $record['tripId']),
            'stop_id'  => $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $record['stopId']),
            'sequence' => $record['stopSequence'],
        ];
    }

    /**
     * The track's stops that exist, in order of their sequence, numbered from 0.
     *
     * @param iterable<array{track_id: string, trip_id: string, stop_id: string, sequence: int}> $stops
     *
     * @return iterable<array{stop_id: string, track_id: string, sequence: int}>
     */
    private function numberedStops(string $trackId, iterable $stops, Set $existingStopIds): iterable
    {
        $generator = new SequenceGenerator();

        return $stops
            |> c\filter(fn (array $stop) => $existingStopIds->contains($stop['stop_id']))
            |> c\sortBy(fn (array $stop) => $stop['sequence'])
            |> c\map(fn (array $stop) => [
                'stop_id'  => $stop['stop_id'],
                'track_id' => $trackId,
                'sequence' => $generator->next(),
            ]);
    }

    #[\Override]
    public function getDescription(): string
    {
        return "[ZTM Gdańsk] Import tracks";
    }
}
