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

namespace App\Tests\ZtmGdansk\DataImporter;

use App\DataImport\ImportJobCounts;
use App\DataImport\ProgressReporterInterface;
use App\Event\DataUpdateEvent;
use App\Provider\ZtmGdansk\DataImporter\ZtmGdanskScheduleDataImporter;
use App\Service\IdUtils;
use App\Tests\DataImport\ImporterTestCase;
use App\Tests\DataImport\StubJsonStreamer;
use Doctrine\DBAL\Driver\PDO\Exception as PdoDriverException;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * The schedule is imported per line, with a job per line downloading its days at once, for the lines of this provider
 * updated by the current import, and only for the line+days not imported before.
 */
final class ZtmGdanskScheduleDataImporterTest extends ImporterTestCase
{
    private const string LINE_6 = self::PROVIDER . '::6';
    private const string LINE_8 = self::PROVIDER . '::8';
    private const array WINDOW  = [
        '2026-10-28', '2026-10-29', '2026-10-30', '2026-10-31', '2026-11-01', '2026-11-02', '2026-11-03',
    ];
    private DataUpdateEvent $event;

    /**
     * Routes of the routes feed on every date, unless a test gives its own; line() adds the line's route.
     *
     * @var list<string>
     */
    private array $routes = [];

    /**
     * Warnings left to deadlock the transaction they are given in, see deadlockingReporter().
     */
    private int $deadlocks = 0;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->event = $this->newImport();

        $this->connection->insert('provider', [
            'id'          => 'other',
            'name'        => 'Other',
            'class'       => 'Other',
            'update_date' => self::NOW,
        ]);
        $this->connection->insert('operator', [
            'id'          => self::PROVIDER . '::1',
            'provider_id' => self::PROVIDER,
            'name'        => 'ZTM',
        ]);
        $this->connection->insert('stop', [
            'id'          => self::PROVIDER . '::100',
            'provider_id' => self::PROVIDER,
            'name'        => 'Brama Wyżynna',
            'on_demand'   => 0,
        ]);
    }

    public function testTripStopsAreStampedWithTheImport(): void
    {
        $this->line(self::LINE_6);

        $this->runImporter(
            $this->importer(fn (string $route, string $date) => $date === '2026-10-28' ? [$this->stopTime($route, $date)] : []),
            $this->event,
        );

        $this->assertSame(
            [$this->event->import->getId()->toBinary()],
            $this->connection->fetchFirstColumn('SELECT import_id FROM trip_stop'),
        );
    }

    public function testOnlyLinesOfThisProviderAreImported(): void
    {
        $this->line(self::LINE_6);
        $this->line('other::7');

        $routes = [];
        $this->runImporter($this->importer(function (string $route) use (&$routes) {
            $routes[] = $route;

            return [];
        }), $this->event);

        $this->assertSame(['6'], array_values(array_unique($routes)));
    }

    public function testOnlyMissingDaysOfLinesRunningThenAreDownloaded(): void
    {
        $this->line(self::LINE_6);
        $this->line(self::LINE_8);
        $this->scheduleDay(self::LINE_6, '2026-10-28');

        // line 8 doesn't run after the first day, its schedule there would be a 404
        $downloaded = [];
        $this->runImporter($this->importer(
            function (string $route, string $date) use (&$downloaded) {
                $downloaded[] = [$route, $date];

                return [];
            },
            fn (string $date) => $date === '2026-10-28' ? ['6', '8'] : ['6'],
        ), $this->event);

        $expected = [
            ...array_map(fn (string $date) => ['6', $date], array_slice(self::WINDOW, 1)),
            ['8', '2026-10-28'],
        ];
        sort($expected);
        sort($downloaded);

        $this->assertSame($expected, $downloaded);
    }

    public function testLineDaysAreRequestedConcurrently(): void
    {
        $this->line(self::LINE_6);

        $log = [];
        $this->importer(function (string $route, string $date) use (&$log) {
            $log[] = "request {$date}";

            return new MockResponse((function () use (&$log, $date) {
                $log[] = "read {$date}";

                yield json_encode(['stopTimes' => []]);
            })());
        })->importLine($this->reporter(), $this->event, self::LINE_6, ['2026-10-28', '2026-10-29', '2026-10-30']);

        $requests = array_slice($log, 0, 3);
        $reads    = array_slice($log, 3);
        sort($reads);

        $this->assertSame(['request 2026-10-28', 'request 2026-10-29', 'request 2026-10-30'], $requests);
        $this->assertSame(['read 2026-10-28', 'read 2026-10-29', 'read 2026-10-30'], $reads);
    }

    public function testDaysThatPassedAreForgotten(): void
    {
        $this->line(self::LINE_6);
        $this->scheduleDay(self::LINE_6, '2026-10-27');
        $this->scheduleDay(self::LINE_6, '2026-10-28');

        $this->runImporter($this->importer(fn () => []), $this->event);

        $this->assertSame(self::WINDOW, $this->scheduleDays());
    }

    public function testDayWithoutTripsIsRemembered(): void
    {
        $this->line(self::LINE_6);

        $this->runImporter($this->importer(fn () => []), $this->event);

        $this->assertSame(self::WINDOW, $this->scheduleDays());

        $requests = 0;
        $this->runImporter($this->importer(function () use (&$requests) {
            $requests++;

            return [];
        }), new DataUpdateEvent($this->event->import));

        $this->assertSame(0, $requests);
    }

    public function testSameRunOnTwoDaysGivesTwoTrips(): void
    {
        $this->line(self::LINE_6);

        $this->runImporter($this->importer(fn (string $route, string $date) => in_array($date, ['2026-10-28', '2026-10-29'], true)
            ? [$this->stopTime($route, $date)]
            : []), $this->event);

        $this->assertSame(
            [self::tripOf('6', '2026-10-28'), self::tripOf('6', '2026-10-29')],
            $this->connection->fetchFirstColumn('SELECT id FROM trip ORDER BY id'),
        );
    }

    public function testTripAfterMidnightBelongsToItsServiceDate(): void
    {
        $this->line(self::LINE_6);
        $this->connection->insert('stop', [
            'id'          => self::PROVIDER . '::101',
            'provider_id' => self::PROVIDER,
            'name'        => 'Dworzec Główny',
            'on_demand'   => 0,
        ]);

        $this->importer(fn () => [])->importLineDay($this->reporter(), $this->event, self::LINE_6, '2026-10-28', [
            $this->stopTime('6', '2026-10-28', ['arrivalTime' => '1899-12-30T23:50:00', 'departureTime' => '1899-12-30T23:51:00']),
            $this->stopTime('6', '2026-10-28', ['arrivalTime' => '1899-12-31T00:29:00', 'departureTime' => '1899-12-31T00:30:00', 'stopId' => 101, 'stopSequence' => 1]),
        ]);

        // Warsaw is UTC+1 after 2026-10-25
        $validity = ['valid_from' => '2026-10-28 22:50:00', 'valid_to' => '2026-10-28 23:30:00'];
        $this->assertSame([['id' => self::tripOf('6', '2026-10-28'), ...$validity]], $this->rows('trip', 'id', 'valid_from', 'valid_to'));
        $this->assertSame([$validity, $validity], $this->rows('trip_stop', 'valid_from', 'valid_to'));
    }

    public function testTripOfUnknownTrackVersionIsSkipped(): void
    {
        $this->line(self::LINE_6);

        $this->importer(fn () => [])->importLineDay(
            $this->reporter(),
            $this->event,
            self::LINE_6,
            '2026-10-28',
            $this->stopTimesWithAnUnknownTrack('6', '2026-10-28'),
        );

        $this->assertSame([self::tripOf('6', '2026-10-28')], $this->connection->fetchFirstColumn('SELECT id FROM trip'));
        $this->assertSame([self::tripOf('6', '2026-10-28')], $this->connection->fetchFirstColumn('SELECT trip_id FROM trip_stop'));
        $this->assertCount(1, $this->warnings);
    }

    public function testDayWithOnlyTripsOfUnknownTrackVersionsIsRetriedByTheNextRun(): void
    {
        $this->line(self::LINE_6);

        $this->runImporter($this->importer(fn (string $route, string $date) => $date === '2026-10-28'
            ? [$this->stopTime($route, $date, ['topologyVersionId' => 2])]
            : []), $this->event);

        $this->assertSame([], $this->failures);
        $this->assertCount(1, $this->warnings);
        $this->assertSame(array_slice(self::WINDOW, 1), $this->scheduleDays());
    }

    public function testImportingALineDayTwiceDoesNothing(): void
    {
        $this->line(self::LINE_6);

        $importer = $this->importer(fn () => []);
        $importer->importLineDay($this->reporter(), $this->event, self::LINE_6, '2026-10-28', [$this->stopTime('6', '2026-10-28')]);
        $importer->importLineDay($this->reporter(), $this->event, self::LINE_6, '2026-10-28', [$this->stopTime('6', '2026-10-28')]);

        $this->assertSame([self::tripOf('6', '2026-10-28')], $this->connection->fetchFirstColumn('SELECT id FROM trip'));
        $this->assertSame([self::tripOf('6', '2026-10-28')], $this->connection->fetchFirstColumn('SELECT trip_id FROM trip_stop'));
        $this->assertSame(['2026-10-28'], $this->scheduleDays());
    }

    #[DataProvider('failedDownloads')]
    public function testFailedDayOfALineIsAWarningAndOtherDaysAreImported(MockResponse $response): void
    {
        $this->line(self::LINE_6);
        $this->line(self::LINE_8);

        $this->runImporter($this->importer(fn (string $route, string $date) => match (true) {
            $route === '6' && $date === '2026-10-28' => $response,
            $date === '2026-10-29'                   => [$this->stopTime($route, $date)],
            default                                  => [],
        }), $this->event);

        $this->assertSame([], $this->failures);
        $this->assertCount(1, $this->warnings);
        $this->assertStringStartsWith('Line ' . self::LINE_6 . ' on 2026-10-28: ', $this->warnings[0]);
        // the failed day writes no row, so the next run downloads it again
        $this->assertSame(
            [...array_slice(self::WINDOW, 1), ...self::WINDOW],
            [...$this->scheduleDays(self::LINE_6), ...$this->scheduleDays(self::LINE_8)],
        );
        $this->assertSame(
            [self::tripOf('6', '2026-10-29'), self::tripOf('8', '2026-10-29')],
            $this->connection->fetchFirstColumn('SELECT id FROM trip ORDER BY id'),
        );
    }

    public static function failedDownloads(): iterable
    {
        yield 'not found' => [new MockResponse('', ['http_code' => 404])];
        yield 'timeout' => [new MockResponse([''])];
        yield 'not JSON' => [new MockResponse('<html>Service unavailable</html>')];
        yield 'without stop times' => [new MockResponse('{"error":"maintenance"}')];
    }

    public function testLineWithNoDayImportedFails(): void
    {
        $this->line(self::LINE_6);
        $this->line(self::LINE_8);

        $scheduleSucceeded = $this->runImporter($this->importer(fn (string $route) => $route === '6'
            ? new MockResponse('', ['http_code' => 503])
            : []), $this->event);

        $this->assertTrue($scheduleSucceeded);
        $this->assertCount(1, $this->failures);
        $this->assertStringContainsString(self::LINE_6, $this->failures[0]);
        // each day's warning stays, then the summary warns of the failed line
        $this->assertCount(count(self::WINDOW) + 1, $this->warnings);
        $this->assertSame([], $this->scheduleDays(self::LINE_6));
    }

    public function testLineWhoseDaysWereAllImportedBeforeDoesNotFail(): void
    {
        $this->line(self::LINE_6);
        $this->scheduleDay(self::LINE_6, '2026-10-28');

        $this->importer(fn () => [])->runJob($this->reporter(), $this->event, ['line' => self::LINE_6, 'dates' => ['2026-10-28']]);

        $this->assertSame([], $this->warnings);
    }

    /**
     * @param array{int, int, int} $counts total, done and failed lines
     */
    #[DataProvider('scheduleOutcomes')]
    public function testScheduleFailsOnlyWhenNoLineWasImported(array $counts, bool $succeeds, bool $warns): void
    {
        $this->assertSame($succeeds, $this->importer(fn () => [])->jobsEnded($this->reporter(), new ImportJobCounts(...$counts)));
        $this->assertCount($warns ? 1 : 0, $this->warnings);
    }

    public static function scheduleOutcomes(): iterable
    {
        yield 'every line imported' => [[3, 3, 0], true, false];
        yield 'some lines failed' => [[3, 1, 2], true, true];
        yield 'every line failed' => [[3, 0, 3], false, true];
        yield 'nothing to import' => [[0, 0, 0], true, false];
    }

    public function testProgressCountsLines(): void
    {
        $this->line(self::LINE_6);
        $this->line(self::LINE_8);

        $this->runImporter($this->importer(fn () => []), $this->event);

        $this->assertSame([2.0, 2.0], $this->progressTotals);
    }

    public function testLineDayImportedMeanwhileByAnotherRunIsNotAFailure(): void
    {
        $this->line(self::LINE_6);

        // another run writes the day after this one checked it wasn't imported, so this one collides
        $importer = $this->getMockBuilder(ZtmGdanskScheduleDataImporter::class)
            ->setConstructorArgs([$this->connection, new IdUtils(), new StubJsonStreamer(fn () => []), $this->httpClient(fn () => []), $this->config(), $this->clock()])
            ->onlyMethods(['importLineDay'])
            ->getMock();
        $importer->expects($this->once())->method('importLineDay')->willReturnCallback(function (): void {
            $this->scheduleDay(self::LINE_6, '2026-10-28');
            $this->scheduleDay(self::LINE_6, '2026-10-28');
        });

        $importer->runJob($this->reporter(), $this->event, ['line' => self::LINE_6, 'dates' => ['2026-10-28']]);

        $this->assertSame(['2026-10-28'], $this->scheduleDays());
    }

    public function testCollisionOfALineDayNotImportedIsAFailure(): void
    {
        $this->line(self::LINE_6);
        $this->trip(self::tripOf('6', '2026-10-29'), '2026-10-29 11:01:00');

        $this->expectException(UniqueConstraintViolationException::class);

        $this->importer(fn (string $route, string $date) => [$this->stopTime($route, $date)])->runJob(
            $this->reporter(),
            $this->event,
            ['line' => self::LINE_6, 'dates' => ['2026-10-29']],
        );
    }

    public function testDeadlockRetryDoesNotDownloadAgain(): void
    {
        $this->line(self::LINE_6);

        $requests = 0;
        $this->importer(function (string $route, string $date) use (&$requests) {
            $requests++;

            return $this->stopTimesWithAnUnknownTrack($route, $date);
        })->importLine($this->deadlockingReporter(1), $this->event, self::LINE_6, ['2026-10-29']);

        $this->assertSame(0, $this->deadlocks);
        $this->assertSame(1, $requests);
        $this->assertSame([self::tripOf('6', '2026-10-29')], $this->connection->fetchFirstColumn('SELECT id FROM trip'));
        $this->assertSame(['2026-10-29'], $this->scheduleDays());
    }

    private function deadlock(): DeadlockException
    {
        return new DeadlockException(PdoDriverException::new(new \PDOException('Deadlock found when trying to get lock')), null);
    }

    /**
     * A reporter whose first warnings deadlock the day's transaction they are given in; stopTimesWithAnUnknownTrack() gives
     * a warning per attempt.
     */
    private function deadlockingReporter(int $deadlocks): ProgressReporterInterface
    {
        $this->deadlocks = $deadlocks;

        $reporter = $this->createStub(ProgressReporterInterface::class);
        $reporter->method('milestone')->willReturnCallback(function (): void {
            if ($this->deadlocks > 0) {
                $this->deadlocks--;

                throw $this->deadlock();
            }
        });

        return $reporter;
    }

    private function line(string $id): void
    {
        [$provider, $symbol] = explode('::', $id);

        if ($provider === self::PROVIDER) {
            $this->routes[] = $symbol;
        }

        $this->connection->insert('line', [
            'id'          => $id,
            'provider_id' => $provider,
            'import_id'   => $this->event->import->getId(),
            'symbol'      => $symbol,
            'type'        => 'bus',
            'fast'        => 0,
            'night'       => 0,
        ], ['import_id' => 'uuid']);

        $this->connection->insert('track', [
            'id'      => sprintf('%s::R%sT1V1', $provider, $symbol),
            'line_id' => $id,
        ]);
    }

    /**
     * A trip of line 6's track with a stop.
     */
    private function trip(string $id, string $validTo): void
    {
        $this->connection->insert('trip', [
            'id'          => $id,
            'provider_id' => self::PROVIDER,
            'track_id'    => self::PROVIDER . '::R6T1V1',
            'operator_id' => self::PROVIDER . '::1',
            'valid_to'    => $validTo,
        ]);
        $this->connection->insert('trip_stop', [
            'trip_id'   => $id,
            'stop_id'   => self::PROVIDER . '::100',
            'sequence'  => 0,
            'arrival'   => self::NOW,
            'departure' => self::NOW,
            'valid_to'  => $validTo,
        ]);
    }

    private function scheduleDay(string $lineId, string $date): void
    {
        $this->connection->insert('ztm_gdansk_schedule_day', ['line_id' => $lineId, 'date' => $date, 'imported_at' => self::NOW]);
    }

    /**
     * @return list<string>
     */
    private function scheduleDays(?string $lineId = null): array
    {
        return $this->connection->fetchFirstColumn(
            'SELECT date FROM ztm_gdansk_schedule_day WHERE line_id = COALESCE(:line_id, line_id) ORDER BY date',
            ['line_id' => $lineId],
        );
    }

    /**
     * Id of the trip stopTime() gives on the route and date.
     */
    private static function tripOf(string $route, string $date): string
    {
        return sprintf('%s::R%sT1S%sO1D%s', self::PROVIDER, $route, $route, $date);
    }

    /**
     * A stop time of trip 1 of the route, at the stop 100 at noon.
     *
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function stopTime(string $route, string $date, array $overrides = []): array
    {
        return [
            'routeId'           => $route,
            'busServiceName'    => $route,
            'tripId'            => 1,
            'topologyVersionId' => 1,
            'order'             => 1,
            'agencyId'          => 1,
            'noteDescription'   => null,
            'date'              => $date,
            'arrivalTime'       => '1899-12-30T12:00:00',
            'departureTime'     => '1899-12-30T12:01:00',
            'stopId'            => 100,
            'stopSequence'      => 0,
            ...$overrides,
        ];
    }

    /**
     * Trip 1 of the route, and a trip of an unknown track version, skipped with a warning.
     *
     * @return list<array<string, mixed>>
     */
    private function stopTimesWithAnUnknownTrack(string $route, string $date): array
    {
        return [
            $this->stopTime($route, $date, ['topologyVersionId' => 2, 'order' => 2]),
            $this->stopTime($route, $date),
        ];
    }

    /**
     * @param \Closure(string, string): (iterable<array<string, mixed>>|MockResponse) $schedule stop times by route
     *                                                                                          and date, or the response
     * @param ?\Closure(string): list<string>                                         $routes   routes listed on the
     *                                                                                          date, by default the
     *                                                                                          lines' routes on every date
     */
    private function importer(\Closure $schedule, ?\Closure $routes = null): ZtmGdanskScheduleDataImporter
    {
        $routes ??= fn () => $this->routes;
        $config = $this->config();

        return new ZtmGdanskScheduleDataImporter(
            $this->connection,
            new IdUtils(),
            new StubJsonStreamer(function (string $url, string $branch) use ($routes, $config) {
                $this->assertSame($config->routesUrl, $url);

                return array_map(fn (string $route) => ['routeId' => (int) $route], $routes(explode('.', $branch)[0]));
            }),
            $this->httpClient($schedule),
            $config,
            $this->clock(),
        );
    }

    /**
     * Serves the stopTimes feed of a route and date.
     *
     * @param \Closure(string, string): (iterable<array<string, mixed>>|MockResponse) $schedule
     */
    private function httpClient(\Closure $schedule): MockHttpClient
    {
        $config = $this->config();

        return new MockHttpClient(function (string $method, string $url) use ($schedule, $config): MockResponse {
            $this->assertStringStartsWith($config->apiUrl . ZtmGdanskScheduleDataImporter::SCHEDULE_PATH . '?', $url);
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

            $rows = $schedule($query['routeId'], $query['date']);

            return $rows instanceof MockResponse ? $rows : new MockResponse(json_encode(['stopTimes' => [...$rows]]));
        });
    }
}
