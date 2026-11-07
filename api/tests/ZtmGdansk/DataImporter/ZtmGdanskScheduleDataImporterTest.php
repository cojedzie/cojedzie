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

use App\Event\DataUpdateEvent;
use App\Parser\Exception\StreamOpenException;
use App\Parser\Exception\StreamReadException;
use App\Provider\ZtmGdansk\DataImporter\ZtmGdanskScheduleDataImporter;
use App\Service\IdUtils;
use App\Tests\DataImport\ImporterTestCase;
use App\Tests\DataImport\StubJsonStreamer;
use Doctrine\DBAL\Driver\PDO\Exception as PdoDriverException;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The schedule is imported per line and day of the window, for the lines of this provider updated by the current
 * import, and only for the line+days not imported before.
 */
final class ZtmGdanskScheduleDataImporterTest extends ImporterTestCase
{
    private const array WINDOW = [
        '2026-10-28', '2026-10-29', '2026-10-30', '2026-10-31', '2026-11-01', '2026-11-02', '2026-11-03',
    ];
    private DataUpdateEvent $event;

    /**
     * Routes of the routes feed on every date, unless a test gives its own; line() adds the line's route.
     *
     * @var list<string>
     */
    private array $routes = [];

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
        $this->line(self::PROVIDER . '::6');

        $this->runImporter(
            $this->importer(fn (string $route, string $date) => $date === '2026-10-28' ? [$this->row($route, $date)] : []),
            $this->event,
        );

        $this->assertSame(
            [$this->event->import->getId()->toBinary()],
            $this->connection->fetchFirstColumn('SELECT import_id FROM trip_stop'),
        );
    }

    public function testOnlyLinesOfThisProviderAreImported(): void
    {
        $this->line(self::PROVIDER . '::6');
        $this->line('other::7');

        $routes = [];
        $this->runImporter($this->importer(function (string $route) use (&$routes) {
            $routes[] = $route;

            return [];
        }), $this->event);

        $this->assertSame(['6'], array_values(array_unique($routes)));
    }

    #[DataProvider('unavailableSchedules')]
    public function testLineWithUnavailableScheduleIsSkipped(\Exception $exception): void
    {
        $this->line(self::PROVIDER . '::6');
        $this->line(self::PROVIDER . '::8');

        $this->runImporter($this->importer(fn (string $route, string $date) => match (true) {
            $route === '6'         => throw $exception,
            $date === '2026-10-28' => [$this->row($route, $date)],
            default                => [],
        }), $this->event);

        $this->assertSame([self::PROVIDER . '::8-8-1-1-2026-10-28'], $this->connection->fetchFirstColumn('SELECT id FROM trip'));
        $this->assertCount(count(self::WINDOW), $this->failures);
    }

    public static function unavailableSchedules(): iterable
    {
        yield 'cannot open' => [new StreamOpenException('Cannot open')];
        yield 'stalls' => [new StreamReadException('Reading the stream timed out.')];
    }

    public function testLineIsPlannedOnlyOnDatesItIsListedOn(): void
    {
        $this->line(self::PROVIDER . '::6');
        $this->line(self::PROVIDER . '::8');

        // line 8 doesn't run after the first day, its schedule there would be a 404
        $this->importer(
            fn () => [],
            fn (string $date) => $date === '2026-10-28' ? ['6', '8'] : ['6'],
        )->import($this->reporter(), $this->event);

        $planned = $this->event->getJobs();
        $this->assertCount(8, $planned);
        $this->assertSame(
            [['line' => self::PROVIDER . '::8', 'date' => '2026-10-28']],
            array_values(array_filter($planned, fn (array $payload) => $payload['line'] === self::PROVIDER . '::8')),
        );
    }

    public function testOnlyMissingDaysAreDownloaded(): void
    {
        $this->line(self::PROVIDER . '::6');
        $this->scheduleDay(self::PROVIDER . '::6', '2026-10-28');

        $dates = [];
        $this->runImporter($this->importer(function (string $route, string $date) use (&$dates) {
            $dates[] = $date;

            return [];
        }), $this->event);

        $this->assertSame(array_slice(self::WINDOW, 1), $dates);
    }

    public function testDayWithoutTripsIsRemembered(): void
    {
        $this->line(self::PROVIDER . '::6');

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
        $this->line(self::PROVIDER . '::6');

        $this->runImporter($this->importer(fn (string $route, string $date) => in_array($date, ['2026-10-28', '2026-10-29'], true)
            ? [$this->row($route, $date)]
            : []), $this->event);

        $this->assertSame(
            [self::PROVIDER . '::6-6-1-1-2026-10-28', self::PROVIDER . '::6-6-1-1-2026-10-29'],
            $this->connection->fetchFirstColumn('SELECT id FROM trip ORDER BY id'),
        );
    }

    public function testTripAfterMidnightBelongsToItsServiceDate(): void
    {
        $this->line(self::PROVIDER . '::6');
        $this->connection->insert('stop', [
            'id'          => self::PROVIDER . '::101',
            'provider_id' => self::PROVIDER,
            'name'        => 'Dworzec Główny',
            'on_demand'   => 0,
        ]);

        $this->importer(fn (string $route, string $date) => [
            $this->row($route, $date, ['arrivalTime' => '1899-12-30T23:50:00', 'departureTime' => '1899-12-30T23:51:00']),
            $this->row($route, $date, ['arrivalTime' => '1899-12-31T00:29:00', 'departureTime' => '1899-12-31T00:30:00', 'stopId' => 101, 'stopSequence' => 1]),
        ])->importLineDay($this->reporter(), $this->event, self::PROVIDER . '::6', '2026-10-28');

        // Warsaw is UTC+1 after 2026-10-25
        $validity = ['valid_from' => '2026-10-28 22:50:00', 'valid_to' => '2026-10-28 23:30:00'];
        $this->assertSame([['id' => self::PROVIDER . '::6-6-1-1-2026-10-28', ...$validity]], $this->rows('trip', 'id', 'valid_from', 'valid_to'));
        $this->assertSame([$validity, $validity], $this->rows('trip_stop', 'valid_from', 'valid_to'));
    }

    public function testTripOfUnknownTrackVersionIsSkipped(): void
    {
        $this->line(self::PROVIDER . '::6');

        $this->importer(fn (string $route, string $date) => [
            $this->row($route, $date, ['topologyVersionId' => 2, 'order' => 2]),
            $this->row($route, $date),
        ])->importLineDay($this->reporter(), $this->event, self::PROVIDER . '::6', '2026-10-28');

        $this->assertSame([self::PROVIDER . '::6-6-1-1-2026-10-28'], $this->connection->fetchFirstColumn('SELECT id FROM trip'));
        $this->assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM trip_stop'));
        $this->assertCount(1, $this->warnings);
    }

    public function testDayWithOnlyTripsOfUnknownTrackVersionsIsRetriedByTheNextRun(): void
    {
        $this->line(self::PROVIDER . '::6');

        $this->runImporter($this->importer(fn (string $route, string $date) => $date === '2026-10-28'
            ? [$this->row($route, $date, ['topologyVersionId' => 2])]
            : []), $this->event);

        $this->assertSame([], $this->failures);
        $this->assertCount(1, $this->warnings);
        $this->assertSame(array_slice(self::WINDOW, 1), $this->scheduleDays());
    }

    public function testImportingALineDayTwiceDoesNothing(): void
    {
        $this->line(self::PROVIDER . '::6');

        $requests = 0;
        $importer = $this->importer(function (string $route, string $date) use (&$requests) {
            $requests++;

            return [$this->row($route, $date)];
        });

        $importer->importLineDay($this->reporter(), $this->event, self::PROVIDER . '::6', '2026-10-28');
        $importer->importLineDay($this->reporter(), $this->event, self::PROVIDER . '::6', '2026-10-28');

        $this->assertSame(1, $requests);
        $this->assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM trip'));
        $this->assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM trip_stop'));
        $this->assertSame(['2026-10-28'], $this->scheduleDays());
    }

    public function testFailedDayWritesNoRow(): void
    {
        $this->line(self::PROVIDER . '::6');

        $this->runImporter($this->importer(fn (string $route, string $date) => match ($date) {
            '2026-10-28' => throw new StreamReadException('Reading the stream timed out.'),
            '2026-10-29' => [$this->row($route, $date)],
            default      => [],
        }), $this->event);

        $this->assertSame(array_slice(self::WINDOW, 1), $this->scheduleDays());
        $this->assertSame([self::PROVIDER . '::6-6-1-1-2026-10-29'], $this->connection->fetchFirstColumn('SELECT id FROM trip'));
        $this->assertSame(['Reading the stream timed out.'], $this->failures);
    }

    public function testLineDayImportedMeanwhileByAnotherRunIsNotAFailure(): void
    {
        $this->line(self::PROVIDER . '::6');

        // another run writes the day after this one checked it wasn't imported, so this one collides
        $importer = $this->getMockBuilder(ZtmGdanskScheduleDataImporter::class)
            ->setConstructorArgs([$this->connection, new IdUtils(), new StubJsonStreamer(fn () => []), $this->config(), $this->clock()])
            ->onlyMethods(['importLineDay'])
            ->getMock();
        $importer->expects($this->once())->method('importLineDay')->willReturnCallback(function (): void {
            $this->scheduleDay(self::PROVIDER . '::6', '2026-10-28');
            $this->scheduleDay(self::PROVIDER . '::6', '2026-10-28');
        });

        $importer->runJob($this->reporter(), $this->event, ['line' => self::PROVIDER . '::6', 'date' => '2026-10-28']);

        $this->assertSame(['2026-10-28'], $this->scheduleDays());
    }

    public function testCollisionOfALineDayNotImportedIsAFailure(): void
    {
        $this->line(self::PROVIDER . '::6');
        $this->trip(self::PROVIDER . '::6-6-1-1-2026-10-29', '2026-10-29 11:01:00');

        $this->expectException(UniqueConstraintViolationException::class);

        $this->importer(fn (string $route, string $date) => [$this->row($route, $date)])->runJob(
            $this->reporter(),
            $this->event,
            ['line' => self::PROVIDER . '::6', 'date' => '2026-10-29'],
        );
    }

    public function testLineDayIsRetriedAfterADeadlock(): void
    {
        $this->line(self::PROVIDER . '::6');

        $attempts = 0;
        $this->importer(function (string $route, string $date) use (&$attempts) {
            if (++$attempts === 1) {
                throw $this->deadlock();
            }

            return [$this->row($route, $date)];
        })->importLineDay($this->reporter(), $this->event, self::PROVIDER . '::6', '2026-10-29');

        $this->assertSame(2, $attempts);
        $this->assertSame([self::PROVIDER . '::6-6-1-1-2026-10-29'], $this->connection->fetchFirstColumn('SELECT id FROM trip'));
        $this->assertSame(['2026-10-29'], $this->scheduleDays());
    }

    public function testLineDayFailsAfterThreeDeadlocks(): void
    {
        $this->line(self::PROVIDER . '::6');

        $attempts = 0;
        $importer = $this->importer(function () use (&$attempts) {
            $attempts++;

            throw $this->deadlock();
        });

        try {
            $importer->importLineDay($this->reporter(), $this->event, self::PROVIDER . '::6', '2026-10-29');
            $this->fail('The deadlock was not rethrown.');
        } catch (DeadlockException) {
        }

        $this->assertSame(3, $attempts);
        $this->assertSame([], $this->scheduleDays());
    }

    private function deadlock(): DeadlockException
    {
        return new DeadlockException(PdoDriverException::new(new \PDOException('Deadlock found when trying to get lock')), null);
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
        $this->connection->insert('schedule_day', [
            'provider_id' => self::PROVIDER,
            'line_id'     => $lineId,
            'date'        => $date,
            'import_id'   => $this->event->import->getId(),
            'imported_at' => self::NOW,
        ], ['import_id' => 'uuid']);
    }

    /**
     * @return list<string>
     */
    private function scheduleDays(): array
    {
        return $this->connection->fetchFirstColumn('SELECT date FROM schedule_day ORDER BY date');
    }

    /**
     * A stop time of trip 1 of the route, at the stop 100 at noon.
     *
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function row(string $route, string $date, array $overrides = []): array
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
     * @param \Closure(string, string): iterable<array<string, mixed>> $schedule rows by route and date
     * @param ?\Closure(string): list<string>                           $routes   routes listed on the date, by default
     *                                                                            the lines' routes on every date
     */
    private function importer(\Closure $schedule, ?\Closure $routes = null): ZtmGdanskScheduleDataImporter
    {
        $routes ??= fn () => $this->routes;
        $config = $this->config();

        return new ZtmGdanskScheduleDataImporter(
            $this->connection,
            new IdUtils(),
            new StubJsonStreamer(function (string $url, string $branch) use ($schedule, $routes, $config) {
                if ($url === $config->routesUrl) {
                    return array_map(fn (string $route) => ['routeId' => (int) $route], $routes(explode('.', $branch)[0]));
                }

                parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

                return $schedule($query['routeId'], $query['date']);
            }),
            $this->config(),
            $this->clock(),
        );
    }
}
