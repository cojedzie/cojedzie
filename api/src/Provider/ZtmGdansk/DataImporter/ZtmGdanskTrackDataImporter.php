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
        $dates = ZtmGdanskProvider::feedDates($this->clock, $this->config->scheduleDays);

        $existingStopIds = new Set(
            $this->connection->createQueryBuilder()
                ->from('stop', 's')
                ->select('id')
                ->where('s.provider_id = :provider_id')
                ->andWhere('s.import_id = :import_id')
                ->setParameter('provider_id', ZtmGdanskProvider::IDENTIFIER)
                // allow only stops from current import
                ->setParameter('import_id', $event->import->getId(), 'uuid')
                ->executeQuery()
                ->iterateColumn()
        );

        // read before the stops in trips, so no stream is left idle while a day of tracks is written
        $trips = $this->tripsByDate($dates);

        /** @var array<string, array{string, string}> $days first and last date listing each track */
        $days  = [];
        $count = 0;
        $day   = 0;

        foreach ($this->jsonStreamer->streamDated($this->config->stopsInTripsUrl, 'stopsInTrip', $dates) as $date => $records) {
            $tripsOfDay = $trips[$date] ?? [];
            $skipped    = 0;
            $imported   = 0;

            foreach ($this->groupTrackStops($records, $existingStopIds) as $trackId => [$tripId, $stops]) {
                // a track listed on several days is written once, from the first day listing it
                if (isset($days[$trackId])) {
                    $days[$trackId] = [min($days[$trackId][0], $date), max($days[$trackId][1], $date)];
                    continue;
                }

                // without its trip the track has no line
                if (!isset($tripsOfDay[$tripId])) {
                    $skipped++;
                    continue;
                }

                $this->upserter->upsert(
                    table: 'track',
                    rows: [$trackId => $tripsOfDay[$tripId]],
                    providerId: ZtmGdanskProvider::IDENTIFIER,
                    import: $event->import,
                );

                $this->replaceTrackStops($trackId, $stops, $event);

                $days[$trackId] = [$date, $date];
                $count++;
                $imported++;
            }

            $reporter->progress(
                ++$day,
                max: count($dates),
                comment: sprintf('%s: %d tracks', $date, $imported),
            );

            if ($skipped > 0) {
                $reporter->milestone(
                    sprintf('Skipped %d tracks of %s without a trip in the trips feed', $skipped, $date),
                    MilestoneType::Warning,
                );
            }

            unset($trips[$date]);
        }

        $this->writeValidity($days, $event);
        $this->expireSupersededVersions(array_keys($days));

        $reporter->progress(count($dates), max: count($dates), comment: sprintf('OK, %d tracks', $count), finished: true);
    }

    /**
     * Trips of the window's days, by date and their R{routeId}T{tripId} id, reduced to what a track needs.
     *
     * @param list<string> $dates
     *
     * @return array<string, array<string, array{line_id: string, description: string}>>
     */
    private function tripsByDate(array $dates): array
    {
        $result = [];
        foreach ($this->jsonStreamer->streamDated($this->config->tripsUrl, 'trips', $dates) as $date => $trips) {
            $result[$date] = [];

            foreach ($trips as $trip) {
                $result[$date][$trip['id']] = [
                    'line_id'     => $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $trip['routeId']),
                    'description' => preg_replace('/\(\d+\)/', '', (string) $trip['tripHeadsign']),
                ];
            }
        }

        return $result;
    }

    /**
     * Replaces the stops of the track and sets its final stop to the last one.
     *
     * @param iterable<array{stop_id: string, track_id: string, sequence: int}> $stops
     */
    private function replaceTrackStops(string $trackId, iterable $stops, DataUpdateEvent $event): void
    {
        $this->connection->delete('track_stop', ['track_id' => $trackId]);

        $final = null;
        foreach ($stops as $stop) {
            $this->connection->insert(
                'track_stop',
                [
                    ...$stop,
                    'import_id' => $event->import->getId(),
                ],
                [
                    'import_id' => 'uuid',
                ]
            );

            $final = $this->connection->lastInsertId();
        }

        // a track whose stops are all unknown has no final stop
        $this->connection->update('track', ['final_id' => $final], ['id' => $trackId]);
    }

    /**
     * Sets the validity of the tracks to the days listing them, keeping the start of tracks imported before, and
     * gives their stops the same validity.
     *
     * @param array<string, array{string, string}> $days first and last date listing each track
     */
    private function writeValidity(array $days, DataUpdateEvent $event): void
    {
        $timezone = new \DateTimeZone(ZtmGdanskProvider::TIMEZONE);

        foreach ($days as $trackId => [$first, $last]) {
            $this->connection->executeStatement(
                'UPDATE track SET valid_from = COALESCE(valid_from, :valid_from), valid_to = :valid_to WHERE id = :id',
                [
                    'valid_from' => new \DateTimeImmutable($first, $timezone),
                    'valid_to'   => new \DateTimeImmutable($last . ' 23:59:59', $timezone),
                    'id'         => $trackId,
                ],
                [
                    'valid_from' => 'datetime',
                    'valid_to'   => 'datetime',
                ],
            );
        }

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
        $now    = \DateTimeImmutable::createFromInterface($this->clock->now())->setTimezone(new \DateTimeZone('UTC'));
        $listed = new Set($listed);
        $routes = new Set(array_map($this->withoutVersion(...), $listed->toArray()));

        $candidates = $this->connection->fetchFirstColumn(
            'SELECT id FROM track WHERE provider_id = :provider_id AND valid_to > :now',
            ['provider_id' => ZtmGdanskProvider::IDENTIFIER, 'now' => $now],
            ['now' => 'datetime_immutable'],
        );

        $superseded = array_values(array_filter(
            $candidates,
            fn (string $id) => !$listed->contains($id) && $routes->contains($this->withoutVersion($id) ?? $id),
        ));

        foreach (array_chunk($superseded, 1000) as $chunk) {
            foreach (['track' => 'id', 'track_stop' => 'track_id'] as $table => $column) {
                $this->connection->executeStatement(
                    sprintf('UPDATE %s SET valid_to = :now WHERE %s IN (:ids) AND valid_to > :now', $table, $column),
                    ['now' => $now, 'ids' => $chunk],
                    ['now' => 'datetime_immutable', 'ids' => ArrayParameterType::STRING],
                );
            }
        }
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
            |> c\map(fn (array $stop) => [
                'track_id' => $this->idUtils->generate(
                    ZtmGdanskProvider::IDENTIFIER,
                    sprintf('R%sT%sV%s', $stop['routeId'], $stop['tripId'], $stop['topologyVersionId'])
                ),
                'trip_id'  => sprintf('R%sT%s', $stop['routeId'], $stop['tripId']),
                'stop_id'  => $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $stop['stopId']),
                'sequence' => $stop['stopSequence'],
            ])
            |> c\groupBy(fn (array $stop) => $stop['track_id']);

        foreach ($tracks as $trackId => $stops) {
            $generator = new SequenceGenerator();

            // a track whose stops are all unknown is still yielded, so its stops from an earlier import are removed
            yield $trackId => [
                ($stops |> c\first())['trip_id'],
                $stops
                    |> c\filter(fn (array $stop) => $existingStopIds->contains($stop['stop_id']))
                    |> c\sortBy(fn (array $stop) => $stop['sequence'])
                    |> c\map(fn (array $stop) => [
                        'stop_id'  => $stop['stop_id'],
                        'track_id' => $trackId,
                        'sequence' => $generator->next(),
                    ]),
            ];
        }
    }

    #[\Override]
    public function getDescription(): string
    {
        return "[ZTM Gdańsk] Import tracks";
    }
}
