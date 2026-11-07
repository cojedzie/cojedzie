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
use App\Parser\Exception\EndOfStreamException;
use App\Parser\Exception\StreamOpenException;
use App\Parser\Exception\StreamReadException;
use App\Parser\Exception\UnexpectedTokenException;
use App\Provider\ZtmGdansk\ZtmGdanskConfig;
use App\Provider\ZtmGdansk\ZtmGdanskProvider;
use App\Service\AbstractDataImporter;
use App\Service\IdUtils;
use App\Service\JsonStreamer;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Types\Types;
use Ds\Set;
use Psr\Clock\ClockInterface;

/**
 * Imports the schedule per line and day of the window. A line+day is downloaded once: its schedule_day row,
 * written also when the day has no trips, says it was imported.
 */
class ZtmGdanskScheduleDataImporter extends AbstractDataImporter
{
    final public const string SCHEDULE_PATH = "/stopTimes";

    // stopTimes give times as a moment on this day, the next day being past midnight
    private const string TIME_BASE = '1899-12-30';

    public function __construct(
        private readonly Connection $connection,
        private readonly IdUtils $idUtils,
        private readonly JsonStreamer $jsonStreamer,
        private readonly ZtmGdanskConfig $config,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * The importer's own task plans the download and spawns a task per line+day, which imports it.
     */
    #[\Override]
    public function import(ProgressReporterInterface $reporter, DataUpdateEvent $event): void
    {
        if ($event->payload === null) {
            $plan = $this->plan($event);

            foreach ($plan as ['line' => $lineId, 'date' => $date]) {
                $event->spawn(
                    self::class,
                    [
                        'line' => $lineId,
                        'date' => $date,
                    ],
                    sprintf('[ZTM Gdańsk] Schedule of %s on %s', $this->idUtils->strip($lineId), $date),
                );
            }

            $reporter->milestone(sprintf('Planned %d line+days to download', count($plan)));

            return;
        }

        ['line' => $lineId, 'date' => $date] = $event->payload;

        try {
            $this->importLineDay($reporter, $event, $lineId, $date);
        } catch (UniqueConstraintViolationException $exception) {
            // another import (one this run superseded) wrote the line+day after it was checked, any other
            // collision is a failure
            if (!$this->isImported($lineId, $date)) {
                throw $exception;
            }

            $reporter->milestone(sprintf('Line %s on %s was imported meanwhile', $lineId, $date));
        }
    }

    /**
     * Line+days to download: the lines of the current import on each day of the window, but those imported before.
     * The only place deciding what needs a download.
     *
     * @return list<array{line: string, date: string}>
     */
    public function plan(DataUpdateEvent $event): array
    {
        $dates = ZtmGdanskProvider::feedDates($this->clock, $this->config->scheduleDays);

        $lineIds = $this->connection->createQueryBuilder()
            ->from('line', 'l')
            ->select('id')
            ->where('l.provider_id = :provider_id')
            ->andWhere('l.import_id = :import_id')
            ->orderBy('id')
            ->setParameter('provider_id', ZtmGdanskProvider::IDENTIFIER)
            ->setParameter('import_id', $event->import->getId(), 'uuid')
            ->executeQuery()
            ->fetchFirstColumn();

        $imported = new Set();
        $rows     = $this->connection->createQueryBuilder()
            ->from('schedule_day', 'd')
            ->select('line_id', 'date')
            ->where('d.provider_id = :provider_id')
            ->andWhere('d.date IN (:dates)')
            ->setParameter('provider_id', ZtmGdanskProvider::IDENTIFIER)
            ->setParameter('dates', $dates, ArrayParameterType::STRING)
            ->executeQuery()
            ->iterateAssociative();

        foreach ($rows as $row) {
            $imported->add($row['line_id'] . '@' . $row['date']);
        }

        $plan = [];
        foreach ($lineIds as $lineId) {
            foreach ($dates as $date) {
                if (!$imported->contains($lineId . '@' . $date)) {
                    $plan[] = ['line' => $lineId, 'date' => $date];
                }
            }
        }

        return $plan;
    }

    /**
     * Downloads and stores the trips of the line on the date, in one transaction, unless it was imported before.
     *
     * @throws StreamOpenException|StreamReadException|UnexpectedTokenException|EndOfStreamException when the feed fails,
     *     leaving nothing written, so the next import retries it
     * @throws UniqueConstraintViolationException when another import wrote it meanwhile
     */
    public function importLineDay(ProgressReporterInterface $reporter, DataUpdateEvent $event, string $lineId, string $date): void
    {
        $this->connection->transactional(function () use ($reporter, $event, $lineId, $date): void {
            if ($this->isImported($lineId, $date)) {
                return;
            }

            $trackIds = new Set(
                $this->connection->createQueryBuilder()
                    ->from('track')
                    ->select('id')
                    ->where('line_id = :line_id')
                    ->setParameter('line_id', $lineId)
                    ->executeQuery()
                    ->iterateColumn()
            );

            $stopIds = new Set(
                $this->connection->createQueryBuilder()
                    ->from('stop')
                    ->select('id')
                    ->where('provider_id = :provider_id')
                    ->setParameter('provider_id', ZtmGdanskProvider::IDENTIFIER)
                    ->executeQuery()
                    ->iterateColumn()
            );

            $skipped = 0;
            foreach ($this->readTrips($lineId, $date) as $tripId => [$trip, $stops]) {
                // trips refer to the track version of their day, written by the track importer
                if (!$trackIds->contains($trip['track_id'])) {
                    $skipped++;
                    continue;
                }

                $this->saveTrip($event, $tripId, $trip, $stops, $stopIds);
            }

            if ($skipped > 0) {
                $reporter->milestone(
                    sprintf('Skipped %d trips of line %s on %s with an unknown track', $skipped, $lineId, $date),
                    MilestoneType::Warning,
                );
            }

            $this->connection->insert(
                'schedule_day',
                [
                    'provider_id' => ZtmGdanskProvider::IDENTIFIER,
                    'line_id'     => $lineId,
                    'date'        => $date,
                    'import_id'   => $event->import->getId(),
                    'imported_at' => $this->clock->now()->setTimezone(new \DateTimeZone('UTC')),
                ],
                [
                    'import_id'   => 'uuid',
                    'imported_at' => Types::DATETIME_IMMUTABLE,
                ]
            );
        });
    }

    private function isImported(string $lineId, string $date): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM schedule_day WHERE line_id = :line_id AND date = :date',
            ['line_id' => $lineId, 'date' => $date],
        );
    }

    /**
     * Reads the whole feed of the line+day first, as a trip's validity spans all its stop times.
     *
     * @return array<string, array{array<string, mixed>, list<array<string, mixed>>}> trip columns and stops, by trip id
     */
    private function readTrips(string $lineId, string $date): array
    {
        $url = sprintf(
            "%s?%s",
            $this->config->apiUrl . self::SCHEDULE_PATH,
            http_build_query([
                'date'    => $date,
                'routeId' => $this->idUtils->strip($lineId),
            ])
        );

        $midnight = CarbonImmutable::createFromFormat('!Y-m-d', $date, ZtmGdanskProvider::TIMEZONE);
        $base     = new \DateTimeImmutable(self::TIME_BASE, new \DateTimeZone('UTC'));
        $time     = fn (string $value) => $midnight
            ->add($base->diff(new \DateTimeImmutable($value, new \DateTimeZone('UTC'))))
            ->setTimezone('UTC');

        $trips = [];
        foreach ($this->jsonStreamer->stream($url, 'stopTimes') as $stop) {
            $tripId = sprintf('%s-%s-%s-%d-%s', $stop['routeId'], $stop['busServiceName'], $stop['tripId'], $stop['order'], $date);
            $tripId = $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $tripId);

            $arrival   = $time($stop['arrivalTime']);
            $departure = $time($stop['departureTime']);

            $trips[$tripId] ??= [
                [
                    'operator_id' => $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $stop['agencyId']),
                    'track_id'    => $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, sprintf('R%sT%sV%s', $stop['routeId'], $stop['tripId'], $stop['topologyVersionId'])),
                    'note'        => $stop['noteDescription'],
                    'valid_from'  => $arrival,
                    'valid_to'    => $departure,
                ],
                [],
            ];

            $trips[$tripId][0]['valid_from'] = min($trips[$tripId][0]['valid_from'], $arrival);
            $trips[$tripId][0]['valid_to']   = max($trips[$tripId][0]['valid_to'], $departure);

            $trips[$tripId][1][] = [
                'stop_id'   => $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $stop['stopId']),
                'sequence'  => $stop['stopSequence'],
                'arrival'   => $arrival,
                'departure' => $departure,
            ];
        }

        return $trips;
    }

    /**
     * Saves the trip and its stops at known stops, which inherit the trip's validity.
     *
     * @param array<string, mixed>       $trip
     * @param list<array<string, mixed>> $stops
     * @param Set<string>                $stopIds stops of the provider
     */
    private function saveTrip(DataUpdateEvent $event, string $tripId, array $trip, array $stops, Set $stopIds): void
    {
        $types = [
            'import_id'  => 'uuid',
            'valid_from' => Types::DATETIME_IMMUTABLE,
            'valid_to'   => Types::DATETIME_IMMUTABLE,
        ];

        $this->connection->insert(
            'trip',
            [
                ...$trip,
                'id'          => $tripId,
                'provider_id' => ZtmGdanskProvider::IDENTIFIER,
                'import_id'   => $event->import->getId(),
            ],
            $types,
        );

        foreach ($stops as $stop) {
            if (!$stopIds->contains($stop['stop_id'])) {
                continue;
            }

            $this->connection->insert(
                'trip_stop',
                [
                    ...$stop,
                    'trip_id'    => $tripId,
                    'import_id'  => $event->import->getId(),
                    'valid_from' => $trip['valid_from'],
                    'valid_to'   => $trip['valid_to'],
                ],
                [
                    ...$types,
                    'arrival'   => Types::DATETIME_IMMUTABLE,
                    'departure' => Types::DATETIME_IMMUTABLE,
                ]
            );
        }
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            ZtmGdanskStopDataImporter::class,
            ZtmGdanskLineDataImporter::class,
            ZtmGdanskTrackDataImporter::class,
        ];
    }

    #[\Override]
    public function getDescription(): string
    {
        return "[ZTM Gdańsk] Import schedule";
    }
}
