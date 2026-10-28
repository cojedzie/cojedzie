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

use App\DataImport\ProgressReporterInterface;
use App\DataImport\TableUpserter;
use App\Event\DataUpdateEvent;
use App\Provider\ZtmGdansk\ZtmGdanskConfig;
use App\Provider\ZtmGdansk\ZtmGdanskProvider;
use App\Service\AbstractDataImporter;
use App\Service\IdUtils;
use App\Service\JsonStreamer;
use App\Utility\SequenceGenerator;
use Doctrine\DBAL\Connection;
use Ds\Set;
use Kadet\Functional\Collections as c;
use Psr\Clock\ClockInterface;

class ZtmGdanskTrackDataImporter extends AbstractDataImporter
{
    private int $trackCount;

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
    public function import(ProgressReporterInterface $reporter, DataUpdateEvent $event)
    {
        $this->importTracksFromZtmApi($reporter->subtask('Import tracks'), $event);
        $this->importStopsInTracksFromZtmApi($reporter->subtask('Import stops in tracks'), $event);
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            ZtmGdanskStopDataImporter::class,
            ZtmGdanskLineDataImporter::class,
        ];
    }

    private function importTracksFromZtmApi(ProgressReporterInterface $reporter, DataUpdateEvent $event)
    {
        $this->trackCount = $this->connection->transactional(fn () => $this->upserter->upsert(
            'track',
            $this->getTracksFromZtmApi(),
            ZtmGdanskProvider::IDENTIFIER,
            $event->import,
            reporter: $reporter,
        ));

        $reporter->progress($this->trackCount, comment: 'OK', finished: true);
    }

    private function importStopsInTracksFromZtmApi(ProgressReporterInterface $reporter, DataUpdateEvent $event)
    {
        $this->connection->transactional(fn () => $this->importStopsInTracks($reporter, $event));
    }

    private function importStopsInTracks(ProgressReporterInterface $reporter, DataUpdateEvent $event): void
    {
        $deleteStopsSql = $this->connection->createQueryBuilder()
            ->delete('track_stop')
            ->where('track_id = :tid')
            ->getSQL();

        $updateFinalStopInTrackSql = $this->connection->createQueryBuilder()
            ->update('track')
            ->set('final_id', ':sid')
            ->where('id = :tid')
            ->getSQL();

        $deleteStopsPreparedQuery            = $this->connection->prepare($deleteStopsSql);
        $updateFinalStopInTrackPreparedQuery = $this->connection->prepare($updateFinalStopInTrackSql);

        $count = 0;
        foreach ($this->getTrackStopsFromZtmApi($reporter, $event) as $trackId => $stops) {
            // clean all stops related with this track
            $deleteStopsPreparedQuery->bindValue('tid', $trackId);
            $deleteStopsPreparedQuery->executeStatement();
            $hasStops = false;

            foreach ($stops as $stop) {
                $hasStops = true;

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
            }

            $reporter->progress($count += 1, max: $this->trackCount, comment: sprintf('Importing stops in track %s', $trackId));

            if (!$hasStops) {
                continue;
            }

            // set final id on track to last stop
            $updateFinalStopInTrackPreparedQuery->bindValue('tid', $trackId);
            $updateFinalStopInTrackPreparedQuery->bindValue('sid', $this->connection->lastInsertId());
            $updateFinalStopInTrackPreparedQuery->executeStatement();
        }

        $reporter->progress($count, comment: 'Imported stops for all tracks', finished: true);
    }

    private function getTracksFromZtmApi()
    {
        foreach ($this->jsonStreamer->stream($this->config->tripsUrl, sprintf('%s.trips', ZtmGdanskProvider::feedDate($this->clock))) as $track) {
            yield $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $track['id']) => [
                'line_id'     => $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $track['routeId']),
                'description' => preg_replace('/\(\d+\)/', '', (string) $track['tripHeadsign']),
            ];
        }
    }

    private function getTrackStopsFromZtmApi(ProgressReporterInterface $reporter, DataUpdateEvent $event)
    {
        $records = $this->jsonStreamer->stream($this->config->stopsInTripsUrl, sprintf('%s.stopsInTrip', ZtmGdanskProvider::feedDate($this->clock)));

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

        yield from $this->groupTrackStops($records, $existingStopIds);
    }

    /**
     * Groups the stops in trips by track, keeps the stops that exist and numbers them in order of their sequence.
     *
     * The feed isn't ordered by track, so the groups are built in memory. Each record is reduced to its ids and
     * sequence first, so the groups don't hold whole feed records.
     *
     * @param iterable<array> $records
     *
     * @return iterable<string, iterable<array{stop_id: string, track_id: string, sequence: int}>>
     */
    private function groupTrackStops(iterable $records, Set $existingStopIds): iterable
    {
        $tracks = $records
            |> c\map(fn (array $stop) => [
                'track_id' => $this->idUtils->generate(
                    ZtmGdanskProvider::IDENTIFIER,
                    sprintf("R%sT%s", $stop['routeId'], $stop['tripId'])
                ),
                'stop_id'  => $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $stop['stopId']),
                'sequence' => $stop['stopSequence'],
            ])
            |> c\groupBy(fn (array $stop) => $stop['track_id']);

        foreach ($tracks as $trackId => $stops) {
            $generator = new SequenceGenerator();

            // a track whose stops are all unknown is still yielded, so its stops from an earlier import are removed
            yield $trackId => $stops
                |> c\filter(fn (array $stop) => $existingStopIds->contains($stop['stop_id']))
                |> c\sortBy(fn (array $stop) => $stop['sequence'])
                |> c\map(fn (array $stop) => [
                    'stop_id'  => $stop['stop_id'],
                    'track_id' => $trackId,
                    'sequence' => $generator->next(),
                ]);
        }
    }

    #[\Override]
    public function getDescription(): string
    {
        return "[ZTM Gdańsk] Import tracks";
    }
}
