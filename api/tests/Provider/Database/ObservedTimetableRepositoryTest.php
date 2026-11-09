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

namespace App\Tests\Provider\Database;

use App\DepartureHistory\DepartureHistory;
use App\Dto\Departure;
use App\Dto\DepartureState;
use App\Dto\Line;
use App\Dto\Stop;
use App\Dto\TimetableRange;
use App\Filter\Requirement\Requirement;
use App\Provider\Database\ObservedTimetableRepository;
use App\Provider\Live\DepartureStatus;
use App\Provider\Live\LiveDeparture;
use App\Provider\TimetableRepository;
use App\Tests\DepartureHistory\Support\InMemoryDepartureHistory;
use Carbon\Carbon;
use Ds\Seq;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\ErrorHandler\BufferingLogger;

final class ObservedTimetableRepositoryTest extends TestCase
{
    private const string TIMEZONE = 'Europe/Warsaw';
    private InMemoryDepartureHistory $history;
    private BufferingLogger $logger;

    #[\Override]
    protected function setUp(): void
    {
        $this->history = new InMemoryDepartureHistory();
        $this->logger  = new BufferingLogger();
    }

    public function testAnEntryThatLeftShowsItsLastRecordedEstimate(): void
    {
        $this->observe('2026-11-13 11:52', $this->live('2026-11-13 11:50', '2026-11-13 11:53'));

        $departure = $this->single($this->repository($this->history, $this->planned('2026-11-13 11:50')));

        $this->assertSame(DepartureState::Departed, $departure->getState());
        $this->assertSame('2026-11-13 11:53', $this->local($departure->getEstimated()));
        $this->assertSame('9221', $departure->getVehicle()?->getId());
    }

    public function testAFreshEstimateIsRealtime(): void
    {
        $this->observe('2026-11-13 11:59:30', $this->live('2026-11-13 12:05', '2026-11-13 12:08'));

        $departure = $this->single($this->repository($this->history, $this->planned('2026-11-13 12:05')));

        $this->assertSame(DepartureState::Realtime, $departure->getState());
        $this->assertSame('2026-11-13 12:08', $this->local($departure->getEstimated()));
        $this->assertSame('9221', $departure->getVehicle()?->getId());
    }

    public function testAStaleEstimateFallsBackToThePlan(): void
    {
        $this->observe('2026-11-13 11:55', $this->live('2026-11-13 12:05', '2026-11-13 12:08'));

        $departure = $this->single($this->repository($this->history, $this->planned('2026-11-13 12:05')));

        $this->assertSame(DepartureState::Planned, $departure->getState());
        $this->assertNull($departure->getEstimated());
        $this->assertNull($departure->getVehicle());
    }

    public function testARecentObservationWithoutEstimateIsPlanned(): void
    {
        $this->observe('2026-11-13 11:59:50', $this->live('2026-11-13 12:05', null));

        $departure = $this->single($this->repository($this->history, $this->planned('2026-11-13 12:05')));

        $this->assertSame(DepartureState::Planned, $departure->getState());
        $this->assertNull($departure->getEstimated());
    }

    public function testTheSameMinuteOnAnotherDayIsNotPaired(): void
    {
        $this->observe('2026-11-12 12:05', $this->live('2026-11-12 12:05', '2026-11-12 12:20'));
        $this->observe('2026-11-13 11:59:30', $this->live('2026-11-13 12:05', '2026-11-13 12:06'));

        $departures = $this->repository(
            $this->history,
            $this->planned('2026-11-12 12:05'),
            $this->planned('2026-11-13 12:05'),
        )->departures();

        $this->assertSame(
            [
                ['2026-11-12 12:05', 'departed', '2026-11-12 12:20'],
                ['2026-11-13 12:05', 'realtime', '2026-11-13 12:06'],
            ],
            $this->describe($departures),
        );
    }

    public function testLiveOnlyDeparturesAreNotAdded(): void
    {
        $this->observe(
            '2026-11-13 11:59:30',
            $this->live('2026-11-13 12:05', '2026-11-13 12:08'),
            $this->live('2026-11-13 12:06', '2026-11-13 12:07', stop: 'S2'),
        );

        $departures = $this->repository($this->history, $this->planned('2026-11-13 12:05'))->departures();

        $this->assertSame([['2026-11-13 12:05', 'realtime', '2026-11-13 12:08']], $this->describe($departures));
    }

    public function testWithoutHistoryEntriesArePlannedOrDeparted(): void
    {
        $departures = $this->repository(
            new InMemoryDepartureHistory(enabled: false),
            $this->planned('2026-11-13 11:50'),
            $this->planned('2026-11-13 12:05'),
        )->departures();

        $this->assertSame(
            [['2026-11-13 11:50', 'departed', null], ['2026-11-13 12:05', 'planned', null]],
            $this->describe($departures),
        );
    }

    public function testAFailingHistoryIsLoggedAndIgnored(): void
    {
        $this->history->failingReads = true;

        $departures = $this->repository(
            $this->history,
            $this->planned('2026-11-13 11:50'),
            $this->planned('2026-11-13 12:05'),
        )->departures();

        $this->assertSame(
            [['2026-11-13 11:50', 'departed', null], ['2026-11-13 12:05', 'planned', null]],
            $this->describe($departures),
        );

        $warnings = array_filter($this->logger->cleanLogs(), fn (array $log) => $log[0] === 'warning');
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('Storage is down', (string) array_first($warnings)[2]['message']);
    }

    private function repository(DepartureHistory $history, Departure ...$planned): ObservedTimetableRepository
    {
        $inner = new readonly class($planned) implements TimetableRepository {
            public function __construct(
                private array $planned,
            ) {
            }

            #[\Override]
            public function first(Requirement ...$requirements)
            {
                return null;
            }

            #[\Override]
            public function all(Requirement ...$requirements): Seq
            {
                return new Seq();
            }

            #[\Override]
            public function departures(Requirement ...$requirements): Seq
            {
                return new Seq($this->planned);
            }

            #[\Override]
            public function range(): ?TimetableRange
            {
                return null;
            }
        };

        return new ObservedTimetableRepository(
            $inner,
            $history,
            new MockClock(new \DateTimeImmutable('2026-11-13 12:00', new \DateTimeZone(self::TIMEZONE))),
            $this->logger,
            'trojmiasto',
            '1 minute',
        );
    }

    private function single(ObservedTimetableRepository $repository): Departure
    {
        $departures = $repository->departures();
        $this->assertCount(1, $departures);

        return $departures->first();
    }

    private function observe(string $at, LiveDeparture ...$departures): void
    {
        $this->history->record(
            'trojmiasto',
            new \DateTimeImmutable($at, new \DateTimeZone(self::TIMEZONE)),
            $departures,
        );
    }

    private function planned(string $scheduled): Departure
    {
        return Departure::createFromArray([
            'line'      => Line::createFromArray(['id' => 'L1', 'symbol' => '171']),
            'stop'      => Stop::createFromArray(['id' => 'S1', 'name' => 'Stop']),
            'scheduled' => Carbon::parse($scheduled, self::TIMEZONE)->tz('UTC'),
        ]);
    }

    private function live(string $scheduled, ?string $estimated, string $stop = 'S1'): LiveDeparture
    {
        $zone = new \DateTimeZone(self::TIMEZONE);

        return new LiveDeparture(
            stopId: $stop,
            lineId: 'L1',
            lineSymbol: '171',
            tripKey: $scheduled,
            scheduledAt: new \DateTimeImmutable($scheduled, $zone),
            estimatedAt: $estimated !== null ? new \DateTimeImmutable($estimated, $zone) : null,
            status: $estimated !== null ? DepartureStatus::Realtime : DepartureStatus::Scheduled,
            headsign: 'Oliwa',
            vehicle: '9221',
            duty: null,
        );
    }

    private function local(?\DateTimeInterface $time): ?string
    {
        return $time !== null
            ? \DateTimeImmutable::createFromInterface($time)
                ->setTimezone(new \DateTimeZone(self::TIMEZONE))
                ->format('Y-m-d H:i')
            : null;
    }

    /**
     * @param Seq<Departure> $departures
     *
     * @return list<array{string, string, ?string}>
     */
    private function describe(Seq $departures): array
    {
        $result = [];
        foreach ($departures as $departure) {
            $result[] = [
                $this->local($departure->getScheduled()),
                $departure->getState()->value,
                $this->local($departure->getEstimated()),
            ];
        }

        return $result;
    }
}
