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
use App\Event\DataUpdateEvent;
use App\Provider\ZtmGdansk\ZtmGdanskProvider;
use App\Service\AbstractDataImporter;
use App\Service\IdUtils;
use App\Service\IterableUtils;
use Doctrine\DBAL\Connection;
use Illuminate\Support\Collection;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ZtmGdanskTrackDataImporter extends AbstractDataImporter
{
    final public const TRACKS_URL         = ZtmGdanskProvider::BASE_URL . "/b15bb11c-7e06-4685-964e-3db7775f912f/download/trips.json";
    final public const STOPS_IN_TRACK_URL = ZtmGdanskProvider::BASE_URL . "/3115d29d-b763-4af5-93f6-763b835967d6/download/stopsintrips.json";
    private int $trackCount;

    public function __construct(
        private readonly Connection $connection,
        private readonly HttpClientInterface $httpClient,
        private readonly IdUtils $idUtils
    ) {
    }

    public function import(ProgressReporterInterface $reporter, DataUpdateEvent $event)
    {
        $this->importTracksFromZtmApi($reporter->subtask('Import tracks'), $event);
        $this->importStopsInTracksFromZtmApi($reporter->subtask('Import stops in tracks'), $event);
    }

    public function getDependencies(): array
    {
        return [
            ZtmGdanskStopDataImporter::class,
            ZtmGdanskLineDataImporter::class,
        ];
    }

    private function importTracksFromZtmApi(ProgressReporterInterface $reporter, DataUpdateEvent $event)
    {
        $this->connection->beginTransaction();

        $query = $this->connection->createQueryBuilder()
            ->from('track', 't')
            ->select('id')
            ->where('t.provider_id = :provider_id')
            ->andWhere('t.id IN (:ids)')
            ->setParameter('provider_id', ZtmGdanskProvider::IDENTIFIER);

        $count = 0;
        foreach (IterableUtils::batch($this->getTracksFromZtmApi(), 100) as $batch) {
            $ids = array_keys($batch);
            $query->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY);
            $existing = $query->execute()->fetchFirstColumn();

            foreach ($batch as $id => $track) {
                if (in_array($id, $existing, true)) {
                    $this->connection->update(
                        'track',
                        [
                            ...$track,
                            'import_id' => $event->import->getId(),
                        ],
                        [
                            'id'          => $id,
                            'provider_id' => ZtmGdanskProvider::IDENTIFIER,
                        ],
                        [
                            'import_id' => 'uuid',
                        ]
                    );
                } else {
                    $this->connection->insert(
                        'track',
                        array_merge(
                            [
                                'id'          => $id,
                                'provider_id' => ZtmGdanskProvider::IDENTIFIER,
                                'import_id'   => $event->import->getId(),
                            ],
                            $track
                        ),
                        [
                            'import_id' => 'uuid',
                        ]
                    );
                }
            }

            $reporter->progress($count += is_countable($batch) ? count($batch) : 0);
        }

        $reporter->progress($count, comment: 'OK', finished: true);
        $this->connection->commit();

        $this->trackCount = $count;
    }

    private function importStopsInTracksFromZtmApi(ProgressReporterInterface $reporter, DataUpdateEvent $event)
    {
        $this->connection->beginTransaction();

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
        foreach ($this->getTrackStopsFromZtmApi() as $trackId => $stops) {
            // clean all stops related with this track
            $deleteStopsPreparedQuery->executeQuery(['tid' => $trackId]);

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
            }

            $reporter->progress($count += 1, max: $this->trackCount, comment: sprintf('Importing stops in track %s', $trackId));

            // set final id on track to last stop
            $updateFinalStopInTrackPreparedQuery->executeQuery([
                'tid' => $trackId,
                'sid' => $this->connection->lastInsertId(),
            ]);
        }

        $reporter->progress($count, comment: 'Imported stops for all tracks', finished: true);

        $this->connection->commit();
    }

    private function getTracksFromZtmApi()
    {
        $response = $this->httpClient->request('GET', self::TRACKS_URL);
        $tracks   = $response->toArray()[date('Y-m-d')]['trips'];

        foreach ($tracks as $track) {
            yield $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $track['id']) => [
                'line_id'     => $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $track['routeId']),
                'description' => preg_replace('/\(\d+\)/', '', $track['tripHeadsign']),
            ];
        }
    }

    private function getTrackStopsFromZtmApi()
    {
        $response = $this->httpClient->request('GET', self::STOPS_IN_TRACK_URL);
        $all      = $response->toArray()[date('Y-m-d')]['stopsInTrip'];
        $all      = collect($all)->groupBy(
            fn ($stop) => $this->idUtils->generate(
                ZtmGdanskProvider::IDENTIFIER,
                sprintf("R%sT%s", $stop['routeId'], $stop['tripId'])
            )
        );

        $existingStopIds = $this->connection->createQueryBuilder()
            ->from('stop', 's')
            ->select('id')
            ->where('s.provider_id = :provider_id')
            ->setParameter('provider_id', ZtmGdanskProvider::IDENTIFIER)
            ->execute()
            ->fetchFirstColumn();

        /**
         * @var string $trackId
         * @var Collection<array> $stops
         */
        foreach ($all as $trackId => $stops) {
            yield $trackId       => $stops
                ->map(fn ($stop) => [
                    'stop_id'  => $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $stop['stopId']),
                    'track_id' => $trackId,
                    // HACK! Gdynia has 0 based sequence
                    'sequence' => $stop['stopSequence'] + (int) ($stop['stopId'] > 30000),
                ])
                ->filter(fn ($stop) => in_array($stop['stop_id'], $existingStopIds, true))
                ->sortBy(fn ($stop) => $stop['sequence'])
                ->map(fn ($stop, $i) => array_merge($stop, ['sequence' => $i]))
                ->all();
        }
    }

    public function getDescription(): string
    {
        return "[ZTM Gdańsk] Import tracks";
    }
}
