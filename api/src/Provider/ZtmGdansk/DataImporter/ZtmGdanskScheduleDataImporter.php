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

use App\DataImport\ImportJobCounts;
use App\DataImport\MilestoneType;
use App\DataImport\ProgressReporterInterface;
use App\DataImport\SplittingDataImporter;
use App\Event\DataUpdateEvent;
use App\Exception\UnknownTrackVersionException;
use App\Provider\ZtmGdansk\ZtmGdanskConfig;
use App\Provider\ZtmGdansk\ZtmGdanskProvider;
use App\Service\AbstractDataImporter;
use App\Service\IdUtils;
use App\Service\JsonStreamer;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Types\Types;
use Ds\Set;
use Psr\Clock\ClockInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Imports the schedule per line and day of the window, with a job per line downloading the line's days at once.
 * A line+day is downloaded once: its schedule_day row, written also when the day has no trips, says it was imported.
 */
class ZtmGdanskScheduleDataImporter extends AbstractDataImporter implements SplittingDataImporter
{
    final public const string SCHEDULE_PATH = "/stopTimes";

    // stopTimes give times as a moment on this day, the next day being past midnight
    private const string TIME_BASE = '1899-12-30';

    // attempts at a line+day whose transaction deadlocks or times out on a lock
    private const int ATTEMPTS = 3;

    // seconds a download of a line+day may stay idle
    private const int TIMEOUT = 60;

    public function __construct(
        private readonly Connection $connection,
        private readonly IdUtils $idUtils,
        private readonly JsonStreamer $jsonStreamer,
        private readonly HttpClientInterface $httpClient,
        private readonly ZtmGdanskConfig $config,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * Plans the download and hands out a job per line, with the line's days to import.
     */
    #[\Override]
    public function import(ProgressReporterInterface $reporter, DataUpdateEvent $event): void
    {
        $plan = $this->plan($event);

        $lines = [];
        foreach ($plan as ['line' => $lineId, 'date' => $date]) {
            $lines[$lineId][] = $date;
        }

        foreach ($lines as $lineId => $dates) {
            $event->addJob(['line' => $lineId, 'dates' => $dates]);
        }

        $reporter->milestone(sprintf('Planned %d days of %d lines', count($plan), count($lines)));
    }

    #[\Override]
    public function runJob(ProgressReporterInterface $reporter, DataUpdateEvent $event, array $payload): void
    {
        $this->importLine($reporter, $event, $payload['line'], $payload['dates']);
    }

    #[\Override]
    public function jobEnded(ProgressReporterInterface $reporter, ImportJobCounts $counts): void
    {
        $reporter->progress($counts->ended(), max: $counts->total, comment: sprintf('%d lines failed', $counts->failed));
    }

    /**
     * A failed line is retried by the next import, it doesn't fail the schedule; no line imported at all (an outage
     * upstream) does.
     */
    #[\Override]
    public function jobsEnded(ProgressReporterInterface $reporter, ImportJobCounts $counts): bool
    {
        $reporter->milestone(
            sprintf('Imported the schedule of %d lines, %d failed', $counts->done, $counts->failed),
            $counts->failed > 0 ? MilestoneType::Warning : MilestoneType::Info,
        );

        return $counts->total === 0 || $counts->done > 0;
    }

    /**
     * Line+days to download: the lines of the current import on each day of the window the routes feed lists them on
     * (a line that doesn't run on a day has no schedule there), but those imported before. The only place deciding
     * what needs a download.
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

        $listed = new Set();
        foreach ($this->jsonStreamer->streamDated($this->config->routesUrl, 'routes', $dates) as $date => $routes) {
            foreach ($routes as $route) {
                // the line id as the line importer builds it
                $listed->add($this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $route['routeId']) . '@' . $date);
            }
        }

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
                if ($listed->contains($lineId . '@' . $date) && !$imported->contains($lineId . '@' . $date)) {
                    $plan[] = ['line' => $lineId, 'date' => $date];
                }
            }
        }

        return $plan;
    }

    /**
     * Downloads the line's days at once, then stores each in its own transaction. A day that can't be downloaded,
     * isn't a stopTimes feed or has no known track is a warning, the next import retries it.
     *
     * @param list<string> $dates in the window's order
     *
     * @throws \RuntimeException                  when no day of the line was imported, now or before
     * @throws UniqueConstraintViolationException when a day collides with rows of a day not imported
     * @throws RetryableException                 when a day's transaction deadlocked or timed out on a lock in every
     *                                            attempt
     */
    public function importLine(ProgressReporterInterface $reporter, DataUpdateEvent $event, string $lineId, array $dates): void
    {
        // all requests go out before any response is read, so the days download concurrently
        $responses = [];
        foreach ($dates as $date) {
            $responses[$date] = $this->httpClient->request('GET', $this->config->apiUrl . self::SCHEDULE_PATH, [
                'query'   => ['date' => $date, 'routeId' => $this->idUtils->strip($lineId)],
                'timeout' => self::TIMEOUT,
            ]);
        }

        $imported = 0;

        try {
            foreach ($responses as $date => $response) {
                $imported += (int) $this->importDownloadedDay($reporter, $event, $lineId, (string) $date, $response);
            }
        } finally {
            // the days left after a failure are not read, cancelling them keeps their errors from being thrown later
            foreach ($responses as $response) {
                $response->cancel();
            }
        }

        // the line can't be imported at all, e.g. upstream is down, which the schedule's node must see
        if ($imported === 0 && $dates !== []) {
            throw new \RuntimeException(sprintf('No day of line %s could be imported', $lineId));
        }
    }

    /**
     * @return bool whether the day is imported now, by this call or before
     */
    private function importDownloadedDay(
        ProgressReporterInterface $reporter,
        DataUpdateEvent $event,
        string $lineId,
        string $date,
        ResponseInterface $response,
    ): bool {
        try {
            $feed = json_decode($response->getContent(), true, flags: \JSON_THROW_ON_ERROR);

            if (!is_array($feed) || !is_array($feed['stopTimes'] ?? null)) {
                throw new \UnexpectedValueException('The response has no stopTimes');
            }
        } catch (HttpClientException|\JsonException|\UnexpectedValueException $exception) {
            $reporter->milestone(sprintf('Line %s on %s: %s', $lineId, $date, $exception->getMessage()), MilestoneType::Warning);

            return false;
        }

        try {
            $this->importLineDay($reporter, $event, $lineId, $date, $feed['stopTimes']);
        } catch (UniqueConstraintViolationException $exception) {
            // another import (one this run superseded) wrote the line+day after it was checked, any other
            // collision is a failure
            if (!$this->isImported($lineId, $date)) {
                throw $exception;
            }

            $reporter->milestone(sprintf('Line %s on %s was imported meanwhile', $lineId, $date));
        } catch (UnknownTrackVersionException $exception) {
            // the tracks will likely be there for the next import, which plans the day again
            $reporter->milestone($exception->getMessage(), MilestoneType::Warning);

            return false;
        }

        return true;
    }

    /**
     * Stores the trips of the line on the date, in one transaction, unless it was imported before.
     *
     * @param list<array<string, mixed>> $stopTimes the day's stopTimes feed of the line
     *
     * @throws UnknownTrackVersionException       when the day has trips, but none on a known track, leaving nothing written
     * @throws UniqueConstraintViolationException when another import wrote it meanwhile
     * @throws RetryableException                 when the transaction deadlocked or timed out on a lock in every attempt
     */
    public function importLineDay(ProgressReporterInterface $reporter, DataUpdateEvent $event, string $lineId, string $date, array $stopTimes): void
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                $this->importLineDayOnce($reporter, $event, $lineId, $date, $stopTimes);

                return;
            } catch (RetryableException $exception) {
                // workers inserting trips at once can deadlock, the rolled back attempt is retried as a whole
                if ($attempt >= self::ATTEMPTS) {
                    throw $exception;
                }

                usleep(random_int(50_000, 250_000));
            }
        }
    }

    /**
     * @param list<array<string, mixed>> $stopTimes
     */
    private function importLineDayOnce(ProgressReporterInterface $reporter, DataUpdateEvent $event, string $lineId, string $date, array $stopTimes): void
    {
        $this->connection->transactional(function () use ($reporter, $event, $lineId, $date, $stopTimes): void {
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
            $saved   = 0;
            foreach ($this->readTrips($date, $stopTimes) as $tripId => [$trip, $stops]) {
                // trips refer to the track version of their day, written by the track importer
                if (!$trackIds->contains($trip['track_id'])) {
                    $skipped++;
                    continue;
                }

                $this->saveTrip($event, $tripId, $trip, $stops, $stopIds);
                $saved++;
            }

            // nothing would be left of the day, recording it would keep it empty for good
            if ($skipped > 0 && $saved === 0) {
                throw new UnknownTrackVersionException($lineId, $date, $skipped);
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
     * Groups the stop times of the line+day by trip, as a trip's validity spans all its stop times.
     *
     * @param list<array<string, mixed>> $stopTimes
     *
     * @return array<string, array{array<string, mixed>, list<array<string, mixed>>}> trip columns and stops, by trip id
     */
    private function readTrips(string $date, array $stopTimes): array
    {
        $midnight = CarbonImmutable::createFromFormat('!Y-m-d', $date, ZtmGdanskProvider::TIMEZONE);
        $base     = new \DateTimeImmutable(self::TIME_BASE, new \DateTimeZone('UTC'));
        $time     = fn (string $value) => $midnight
            ->add($base->diff(new \DateTimeImmutable($value, new \DateTimeZone('UTC'))))
            ->setTimezone('UTC');

        $trips = [];
        foreach ($stopTimes as $stop) {
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
