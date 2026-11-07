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
use App\Exception\UnknownTrackVersionException;
use App\Parser\Exception\EndOfStreamException;
use App\Parser\Exception\StreamOpenException;
use App\Parser\Exception\StreamReadException;
use App\Parser\Exception\UnexpectedTokenException;
use App\Provider\ZtmGdansk\ZtmGdanskConfig;
use App\Provider\ZtmGdansk\ZtmGdanskProvider;
use App\Service\AbstractDataImporter;
use App\Service\IdUtils;
use App\Service\JsonStreamer;
use App\Utility\Retry;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Types\Types;
use Ds\Seq;
use Ds\Set;
use Kadet\Functional\Collections as c;
use Psr\Clock\ClockInterface;

/**
 * Imports the schedule per line and day of the window, line by line. A line+day is downloaded once: its
 * ztm_gdansk_schedule_day row, written also when the day has no trips, says it was imported. The importer also forgets
 * the days that passed: no other code knows the table.
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
     * Plans the download, then imports the line+days line by line. A failed line is retried by the next import, it
     * doesn't fail the schedule; no line imported at all (an outage upstream) does.
     *
     * @throws \RuntimeException when every line failed
     */
    #[\Override]
    public function import(ProgressReporterInterface $reporter, DataUpdateEvent $event): void
    {
        $this->forgetPassedDays();

        $plan        = $this->plan($event);
        $datesByLine = $this->datesByLine($plan);

        $reporter->milestone(sprintf('Planned %d days of %d lines', count($plan), count($datesByLine)));

        $done   = 0;
        $failed = 0;
        foreach ($datesByLine as $lineId => $dates) {
            if ($this->tryImportLine($reporter, $event, $lineId, $dates)) {
                $done++;
            } else {
                $failed++;
            }

            $reporter->progress($done + $failed, max: count($datesByLine), comment: sprintf('%d lines failed', $failed));
        }

        $this->reportImportedLines($reporter, $done, $failed);
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
        $dates    = ZtmGdanskProvider::feedDates($this->clock, $this->config->scheduleDays);
        $listed   = new Set($this->lineDaysListedInRoutes($dates));
        $imported = new Set($this->lineDaysImported($dates));

        $needsDownload = function (array $lineDay) use ($listed, $imported): bool {
            $key = self::lineDay($lineDay['line'], $lineDay['date']);

            return $listed->contains($key) && !$imported->contains($key);
        };

        return $this->lineDaysOf($this->linesOfImport($event), $dates)
            |> c\filter($needsDownload)
            |> c\values();
    }

    /**
     * Downloads the line's days one by one, storing each in its own transaction. A day whose feed can't be read or
     * has no known track is a warning, the next import retries it.
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
        $imported = $dates
            |> c\map(fn (string $date) => $this->importDay($reporter, $event, $lineId, $date))
            |> c\filter()
            |> c\count();

        // the line can't be imported at all, e.g. upstream is down, which the schedule's node must see
        if ($imported === 0 && $dates !== []) {
            throw new \RuntimeException(sprintf('No day of line %s could be imported', $lineId));
        }
    }

    /**
     * Stores the trips of the line on the date, in one transaction, unless it was imported before. Workers inserting
     * trips at once can deadlock, so a rolled back attempt is retried as a whole.
     *
     * @param list<array<string, mixed>> $stopTimes the day's stopTimes feed of the line
     *
     * @throws UnknownTrackVersionException       when the day has trips, but none on a known track, leaving nothing written
     * @throws UniqueConstraintViolationException when another import wrote it meanwhile
     * @throws RetryableException                 when the transaction deadlocked or timed out on a lock in every attempt
     */
    public function importLineDay(ProgressReporterInterface $reporter, DataUpdateEvent $event, string $lineId, string $date, array $stopTimes): void
    {
        Retry::onLockFailure(fn () => $this->importLineDayOnce($reporter, $event, $lineId, $date, $stopTimes));
    }

    /**
     * @param list<string> $dates
     *
     * @return bool whether the line was imported, a failure is reported as a warning
     */
    private function tryImportLine(ProgressReporterInterface $reporter, DataUpdateEvent $event, string $lineId, array $dates): bool
    {
        try {
            $this->importLine($reporter, $event, $lineId, $dates);

            return true;
        } catch (\Exception $exception) {
            $reporter->milestone(sprintf('Line %s failed: %s', $lineId, $exception->getMessage()), MilestoneType::Warning);

            return false;
        }
    }

    /**
     * @throws \RuntimeException when every line failed
     */
    private function reportImportedLines(ProgressReporterInterface $reporter, int $done, int $failed): void
    {
        $reporter->milestone(
            sprintf('Imported the schedule of %d lines, %d failed', $done, $failed),
            $failed > 0 ? MilestoneType::Warning : MilestoneType::Info,
        );

        if ($done === 0 && $failed > 0) {
            throw new \RuntimeException('No line of the schedule could be imported');
        }
    }

    /**
     * @param list<array{line: string, date: string}> $plan
     *
     * @return array<string, list<string>> dates to import, by line id
     */
    private function datesByLine(array $plan): array
    {
        return $plan
            |> c\groupBy(fn (array $lineDay) => $lineDay['line'])
            |> c\map(fn (Seq $days) => $days |> c\map(fn (array $lineDay) => $lineDay['date']) |> c\toArray())
            |> c\toArray();
    }

    /**
     * Days before today are out of the window and never planned again.
     */
    private function forgetPassedDays(): void
    {
        [$today] = ZtmGdanskProvider::feedDates($this->clock, 1);

        $this->connection->executeStatement('DELETE FROM ztm_gdansk_schedule_day WHERE date < :today', ['today' => $today]);
    }

    /**
     * Identifies a line+day in the sets of plan().
     */
    private static function lineDay(string $line, string $date): string
    {
        return $line . '@' . $date;
    }

    /**
     * @return list<string>
     */
    private function linesOfImport(DataUpdateEvent $event): array
    {
        return $this->connection->createQueryBuilder()
            ->from('line', 'l')
            ->select('id')
            ->where('l.provider_id = :provider_id')
            ->andWhere('l.import_id = :import_id')
            ->orderBy('id')
            ->setParameter('provider_id', ZtmGdanskProvider::IDENTIFIER)
            ->setParameter('import_id', $event->import->getId(), 'uuid')
            ->executeQuery()
            ->fetchFirstColumn();
    }

    /**
     * Every line+day, line by line.
     *
     * @param list<string> $lineIds
     * @param list<string> $dates
     *
     * @return list<array{line: string, date: string}>
     */
    private function lineDaysOf(array $lineIds, array $dates): array
    {
        return $lineIds
            |> c\flatMap(fn (string $lineId) => $dates |> c\map(fn (string $date) => ['line' => $lineId, 'date' => $date]));
    }

    /**
     * @param list<string> $dates
     *
     * @return \Generator<string> line+days the routes feed lists
     */
    private function lineDaysListedInRoutes(array $dates): \Generator
    {
        foreach ($this->jsonStreamer->streamDated($this->config->routesUrl, 'routes', $dates) as $date => $routes) {
            foreach ($routes as $route) {
                yield self::lineDay($this->lineIdOfRoute($route), $date);
            }
        }
    }

    /**
     * The line id as the line importer builds it.
     *
     * @param array<string, mixed> $route
     */
    private function lineIdOfRoute(array $route): string
    {
        return $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $route['routeId']);
    }

    /**
     * @param list<string> $dates
     *
     * @return list<string> line+days with a ztm_gdansk_schedule_day row
     */
    private function lineDaysImported(array $dates): array
    {
        $rows = $this->connection->createQueryBuilder()
            ->from('ztm_gdansk_schedule_day', 'd')
            ->select('line_id', 'date')
            ->where('d.date IN (:dates)')
            ->setParameter('dates', $dates, ArrayParameterType::STRING)
            ->executeQuery()
            ->fetchAllAssociative();

        return $rows |> c\map(fn (array $row) => self::lineDay($row['line_id'], $row['date']));
    }

    /**
     * @return bool whether the day is imported now, by this call or before
     */
    private function importDay(ProgressReporterInterface $reporter, DataUpdateEvent $event, string $lineId, string $date): bool
    {
        $stopTimes = $this->downloadStopTimes($reporter, $lineId, $date);

        return $stopTimes !== null && $this->storeLineDay($reporter, $event, $lineId, $date, $stopTimes);
    }

    /**
     * @return ?list<array<string, mixed>> the day's stopTimes, null when the feed can't be read, reported as a warning
     */
    private function downloadStopTimes(ProgressReporterInterface $reporter, string $lineId, string $date): ?array
    {
        try {
            return iterator_to_array($this->jsonStreamer->stream($this->stopTimesUrl($lineId, $date), 'stopTimes'), false);
        } catch (StreamOpenException|StreamReadException|UnexpectedTokenException|EndOfStreamException $exception) {
            $reporter->milestone(sprintf('Line %s on %s: %s', $lineId, $date, $exception->getMessage()), MilestoneType::Warning);

            return null;
        }
    }

    private function stopTimesUrl(string $lineId, string $date): string
    {
        return $this->config->apiUrl . self::SCHEDULE_PATH . '?' . http_build_query([
            'date'    => $date,
            'routeId' => $this->idUtils->strip($lineId),
        ]);
    }

    /**
     * @param list<array<string, mixed>> $stopTimes
     *
     * @return bool whether the day is imported now, by this call or before
     */
    private function storeLineDay(ProgressReporterInterface $reporter, DataUpdateEvent $event, string $lineId, string $date, array $stopTimes): bool
    {
        try {
            $this->importLineDay($reporter, $event, $lineId, $date, $stopTimes);

            return true;
        } catch (UniqueConstraintViolationException $exception) {
            // another import (one this run superseded) wrote the line+day after it was checked, any other
            // collision is a failure
            if (!$this->isImported($lineId, $date)) {
                throw $exception;
            }

            $reporter->milestone(sprintf('Line %s on %s was imported meanwhile', $lineId, $date));

            return true;
        } catch (UnknownTrackVersionException $exception) {
            // the tracks will likely be there for the next import, which plans the day again
            $reporter->milestone($exception->getMessage(), MilestoneType::Warning);

            return false;
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

            $this->saveTripsOnKnownTracks($reporter, $event, $lineId, $date, $this->readTrips($date, $stopTimes));
            $this->recordImported($lineId, $date);
        });
    }

    private function isImported(string $lineId, string $date): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM ztm_gdansk_schedule_day WHERE line_id = :line_id AND date = :date',
            ['line_id' => $lineId, 'date' => $date],
        );
    }

    /**
     * Trips refer to the track version of their day, written by the track importer; trips on another track are
     * skipped with a warning.
     *
     * @param array<string, array{trip: array<string, mixed>, stops: list<array<string, mixed>>}> $trips by trip id
     *
     * @throws UnknownTrackVersionException when no trip is on a known track, as recording the day would keep it empty
     *                                      for good
     */
    private function saveTripsOnKnownTracks(ProgressReporterInterface $reporter, DataUpdateEvent $event, string $lineId, string $date, array $trips): void
    {
        $trackIds = $this->trackIdsOfLine($lineId);
        $known    = $trips |> c\filter(fn (array $trip) => $trackIds->contains($trip['trip']['track_id']));
        $skipped  = count($trips) - count($known);

        if ($skipped > 0 && $known === []) {
            throw new UnknownTrackVersionException($lineId, $date, $skipped);
        }

        $stopIds = $this->stopIdsOfProvider();
        foreach ($known as $tripId => ['trip' => $trip, 'stops' => $stops]) {
            $this->saveTrip($event, $tripId, $trip, $stops, $stopIds);
        }

        if ($skipped > 0) {
            $reporter->milestone(
                sprintf('Skipped %d trips of line %s on %s with an unknown track', $skipped, $lineId, $date),
                MilestoneType::Warning,
            );
        }
    }

    /**
     * @return Set<string>
     */
    private function trackIdsOfLine(string $lineId): Set
    {
        return new Set(
            $this->connection->createQueryBuilder()
                ->from('track')
                ->select('id')
                ->where('line_id = :line_id')
                ->setParameter('line_id', $lineId)
                ->executeQuery()
                ->iterateColumn()
        );
    }

    /**
     * @return Set<string>
     */
    private function stopIdsOfProvider(): Set
    {
        return new Set(
            $this->connection->createQueryBuilder()
                ->from('stop')
                ->select('id')
                ->where('provider_id = :provider_id')
                ->setParameter('provider_id', ZtmGdanskProvider::IDENTIFIER)
                ->executeQuery()
                ->iterateColumn()
        );
    }

    /**
     * Records the line+day as imported, so it isn't downloaded again.
     */
    private function recordImported(string $lineId, string $date): void
    {
        $this->connection->insert(
            'ztm_gdansk_schedule_day',
            [
                'line_id'     => $lineId,
                'date'        => $date,
                'imported_at' => $this->clock->now()->setTimezone(new \DateTimeZone('UTC')),
            ],
            ['imported_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    /**
     * Groups the stop times of the line+day by trip, as a trip's validity spans all its stop times.
     *
     * @param list<array<string, mixed>> $stopTimes
     *
     * @return array<string, array{trip: array<string, mixed>, stops: list<array<string, mixed>>}> by trip id
     */
    private function readTrips(string $date, array $stopTimes): array
    {
        $midnight = CarbonImmutable::createFromFormat('!Y-m-d', $date, ZtmGdanskProvider::TIMEZONE);

        return $stopTimes
            |> c\groupBy(fn (array $stopTime) => $this->tripIdOf($stopTime, $date))
            |> c\map(fn (Seq $stopTimesOfTrip) => $this->tripWithStops($stopTimesOfTrip, $midnight))
            |> c\toArray();
    }

    /**
     * @param Seq<array<string, mixed>> $stopTimes of one trip
     *
     * @return array{trip: array<string, mixed>, stops: list<array<string, mixed>>}
     */
    private function tripWithStops(Seq $stopTimes, CarbonImmutable $midnight): array
    {
        $stops     = $stopTimes |> c\map(fn (array $stopTime) => $this->tripStopOf($stopTime, $midnight));
        $firstTrip = $this->tripOf($stopTimes->first(), $stops->first());

        return $stops |> c\reduce(fn (array $trip, array $stop) => $this->withStop($trip, $stop), $firstTrip);
    }

    /**
     * Like the track id, letters denote the parts: route, trip, bus service, order of the run and service date.
     *
     * @param array<string, mixed> $stopTime
     */
    private function tripIdOf(array $stopTime, string $date): string
    {
        return $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, sprintf(
            'R%sT%sS%sO%dD%s',
            $stopTime['routeId'],
            $stopTime['tripId'],
            $stopTime['busServiceName'],
            $stopTime['order'],
            $date,
        ));
    }

    /**
     * A trip without stops yet, valid while its first stop.
     *
     * @param array<string, mixed> $stopTime
     * @param array<string, mixed> $stop     the trip stop of the stop time
     *
     * @return array{trip: array<string, mixed>, stops: list<array<string, mixed>>}
     */
    private function tripOf(array $stopTime, array $stop): array
    {
        $trackId = sprintf('R%sT%sV%s', $stopTime['routeId'], $stopTime['tripId'], $stopTime['topologyVersionId']);

        return [
            'trip' => [
                'operator_id' => $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $stopTime['agencyId']),
                'track_id'    => $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $trackId),
                'note'        => $stopTime['noteDescription'],
                'valid_from'  => $stop['arrival'],
                'valid_to'    => $stop['departure'],
            ],
            'stops' => [],
        ];
    }

    /**
     * Adds the stop to the trip, extending the trip's validity to it.
     *
     * @param array{trip: array<string, mixed>, stops: list<array<string, mixed>>} $trip
     * @param array<string, mixed>                                                  $stop
     *
     * @return array{trip: array<string, mixed>, stops: list<array<string, mixed>>}
     */
    private function withStop(array $trip, array $stop): array
    {
        $trip['trip']['valid_from'] = min($trip['trip']['valid_from'], $stop['arrival']);
        $trip['trip']['valid_to']   = max($trip['trip']['valid_to'], $stop['departure']);
        $trip['stops'][]            = $stop;

        return $trip;
    }

    /**
     * @param array<string, mixed> $stopTime
     *
     * @return array<string, mixed>
     */
    private function tripStopOf(array $stopTime, CarbonImmutable $midnight): array
    {
        return [
            'stop_id'   => $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $stopTime['stopId']),
            'sequence'  => $stopTime['stopSequence'],
            'arrival'   => $this->momentOn($midnight, $stopTime['arrivalTime']),
            'departure' => $this->momentOn($midnight, $stopTime['departureTime']),
        ];
    }

    /**
     * The moment, in UTC, of a stopTimes time on the service day starting at the midnight.
     */
    private function momentOn(CarbonImmutable $midnight, string $time): CarbonImmutable
    {
        $base = new \DateTimeImmutable(self::TIME_BASE, new \DateTimeZone('UTC'));

        return $midnight
            ->add($base->diff(new \DateTimeImmutable($time, new \DateTimeZone('UTC'))))
            ->setTimezone('UTC');
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

        $knownStops = $stops |> c\filter(fn (array $stop) => $stopIds->contains($stop['stop_id']));
        foreach ($knownStops as $stop) {
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
