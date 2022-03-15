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
use App\Event\DataUpdateEvent;
use App\Provider\ZtmGdansk\ZtmGdanskProvider;
use App\Service\AbstractDataImporter;
use App\Service\IdUtils;
use App\Service\JsonStreamer;
use Carbon\Carbon;
use Cerbero\JsonObjects\JsonObjectsException;
use Doctrine\DBAL\Connection;
use Ds\Set;

class ZtmGdanskScheduleDataImporter extends AbstractDataImporter
{
    final public const SCHEDULE_URL = "http://ckan2.multimediagdansk.pl/stopTimes";

    public function __construct(
        private readonly Connection $connection,
        private readonly IdUtils $idUtils,
        private readonly JsonStreamer $jsonStreamer,
    ) {
    }

    public function import(ProgressReporterInterface $reporter, DataUpdateEvent $event)
    {
        $existingLineIdsQueryResult = $this->connection->createQueryBuilder()
            ->from('line', 'l')
            ->select('id')
            ->where('l.provider_id = :provider_id')
            ->where('l.import_id = :import_id')
            ->setParameter('provider_id', ZtmGdanskProvider::IDENTIFIER)
            ->setParameter('import_id', $event->import->getId(), 'uuid')
            ->execute();

        $existingLineIds   = $existingLineIdsQueryResult->iterateColumn();
        $existingLineCount = $existingLineIdsQueryResult->rowCount();

        $existingStopIds = new Set(
            $this->connection->createQueryBuilder()
                ->from('stop', 's')
                ->select('id')
                ->where('s.provider_id = :provider_id')
                ->setParameter('provider_id', ZtmGdanskProvider::IDENTIFIER)
                ->execute()
                ->iterateColumn()
        );

        $count = 0;
        foreach ($existingLineIds as $lineId) {
            try {
                $this->connection->beginTransaction();
                $this->importScheduleOfLine($event, $lineId, $existingStopIds);
                $this->connection->commit();
            } catch (JsonObjectsException $exception) {
                $this->connection->rollBack();
                $reporter->milestone("Failed to import line " . $lineId . ": " . $exception->getMessage(), MilestoneType::Warning);
            }
            $reporter->progress($count++, max: $existingLineCount, comment: sprintf("Imported line %s", $lineId));
        }
        $reporter->progress($count, comment: 'Imported all lines', finished: true);
    }

    private function importScheduleOfLine(DataUpdateEvent $event, string $lineId, Set $existingStopIds)
    {
        $tripIds = $this->connection->createQueryBuilder()
            ->from('track')
            ->join('track', 'trip', 'trip', 'trip.track_id = track.id')
            ->select('trip.id')
            ->where('track.line_id = :lid')
            ;

        $this->connection->createQueryBuilder()
            ->delete('trip')
            ->where(sprintf('id in (%s)', $tripIds->getSQL()))
            ->setParameter('ids', $tripIds, Connection::PARAM_STR_ARRAY)
            ->setParameter('lid', $lineId)
            ->execute()
            ;

        $url = sprintf(
            "%s?%s",
            self::SCHEDULE_URL,
            http_build_query([
                'date'    => date('Y-m-d'),
                'routeId' => $this->idUtils->strip($lineId),
            ])
        );

        $trips = new Set();

        $saveStop = function (array $columns) use ($existingStopIds) {
            if (!$existingStopIds->contains($columns['stop_id'])) {
                return;
            }

            $this->connection->insert(
                'trip_stop',
                $columns,
                [
                    'arrival'   => 'datetime',
                    'departure' => 'datetime',
                ]
            );
        };

        $saveTrip = function ($tripId, array $columns) use ($event) {
            $this->connection->insert(
                'trip',
                array_merge(
                    $columns,
                    [
                        'id'          => $tripId,
                        'provider_id' => ZtmGdanskProvider::IDENTIFIER,
                        'import_id'   => $event->import->getId(),
                    ]
                ),
                [
                    'import_id' => 'uuid',
                ]
            );
        };

        foreach ($this->jsonStreamer->stream($url, 'stopTimes') as $stop) {
            $tripId = sprintf('%s-%s-%s-%d', $stop['routeId'], $stop['busServiceName'], $stop['tripId'], $stop['order']);
            $tripId = $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $tripId);

            if (!$trips->contains($tripId)) {
                $saveTrip($tripId, [
                    'operator_id' => $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $stop['agencyId']),
                    'track_id'    => $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, sprintf('R%sT%s', $stop['routeId'], $stop['tripId'])),
                    'provider_id' => ZtmGdanskProvider::IDENTIFIER,
                    'note'        => $stop['noteDescription'],
                ]);

                $trips->add($tripId);
            }

            $base = Carbon::create(1899, 12, 30, 00, 00, 00);
            $date = Carbon::createFromFormat('Y-m-d', $stop['date'], 'Europe/Warsaw')->setTime(00, 00, 00);

            $arrival   = $base->diff(Carbon::createFromTimeString($stop['arrivalTime']));
            $departure = $base->diff(Carbon::createFromTimeString($stop['departureTime']));

            $arrival   = (clone $date)->add($arrival);
            $departure = (clone $date)->add($departure);

            $saveStop([
                'trip_id'   => $tripId,
                'stop_id'   => $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $stop['stopId']),
                'sequence'  => $stop['stopSequence'],
                'arrival'   => $arrival->tz('UTC'),
                'departure' => $departure->tz('UTC'),
            ]);
        }
    }

    public function getDependencies(): array
    {
        return [
            ZtmGdanskStopDataImporter::class,
            ZtmGdanskLineDataImporter::class,
            ZtmGdanskTrackDataImporter::class,
        ];
    }

    public function getDescription(): string
    {
        return "[ZTM Gdańsk] Import schedule";
    }
}
