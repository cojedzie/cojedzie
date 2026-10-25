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

namespace App\Tests\ZtmGdansk;

use App\Dto\Departure;
use App\Dto\Line;
use App\Dto\ScheduledStop;
use App\Dto\Stop;
use App\Dto\Track;
use App\Dto\Trip;
use App\Filter\Requirement\IdConstraint;
use App\Filter\Requirement\LimitConstraint;
use App\Filter\Requirement\Requirement;
use App\Provider\Database\RealtimeScheduleDepartureRepository;
use App\Provider\DepartureRepository;
use App\Provider\LineRepository;
use App\Provider\ScheduleRepository;
use App\Provider\ZtmGdansk\ZtmGdanskRealtimeDepartureRepository;
use App\Service\Proxy\ReferenceFactory;
use App\Tests\ZtmGdansk\Support\EstimatesStream;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;

/**
 * Pins how ZTM's live estimates are joined with the timetable by the shared joining repository.
 */
final class ZtmGdanskDepartureRepositoryTest extends KernelTestCase
{
    private const string ZONE = 'Europe/Warsaw';
    private Stop $stop;

    /**
     * @var array<string, Line>
     */
    private array $lines = [];

    /**
     * @var list<ScheduledStop>
     */
    private array $schedule = [];

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();
        EstimatesStream::register();
        Carbon::setTestNow(Carbon::parse('2026-10-20 12:00', self::ZONE));

        $this->stop = Stop::createFromArray(['id' => '1001',
            'name'                                => 'Brama Wyżynna']);

        foreach (['6', '12'] as $symbol) {
            $this->lines[$symbol] = Line::createFromArray(['id' => $symbol,
                'symbol'                                        => $symbol,
                'type'                                          => Line::TYPE_TRAM]);
        }
    }

    #[\Override]
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function testEstimateIsMatchedToTimetableByLineAndTime(): void
    {
        $this->scheduled('6', '12:05', 'Łostowice', 'trip-6-1205');
        $this->estimate('6', '12:05', 120, 'Lostowice');

        $departures = $this->departures();

        $this->assertCount(1, $departures);
        $this->assertSame('12:07', $this->local($departures[0]->getEstimated()));
        $this->assertSame('12:05', $this->local($departures[0]->getScheduled()));
        $this->assertSame('Łostowice', $departures[0]->getDisplay());
        $this->assertSame('trip-6-1205', $departures[0]->getTrip()->getId());
    }

    public function testEstimateWithoutTimetableIsKept(): void
    {
        $this->estimate('12', '12:10', 0, 'Oliwa');

        $departures = $this->departures();

        $this->assertCount(1, $departures);
        $this->assertSame('12:10', $this->local($departures[0]->getEstimated()));
        $this->assertSame('Oliwa', $departures[0]->getDisplay());
        $this->assertNull($departures[0]->getTrip());
    }

    public function testTimetableEntryWithoutEstimateIsScheduleOnly(): void
    {
        $this->scheduled('6', '12:20', 'Łostowice', 'trip-6-1220');

        $departures = $this->departures();

        $this->assertCount(1, $departures);
        $this->assertNull($departures[0]->getEstimated());
        $this->assertSame('12:20', $this->local($departures[0]->getScheduled()));
        $this->assertSame('Łostowice', $departures[0]->getDisplay());
    }

    public function testDeparturesThatLeftAreDropped(): void
    {
        $this->scheduled('6', '11:50', 'Łostowice', 'trip-6-1150');
        $this->scheduled('12', '11:58', 'Oliwa', 'trip-12-1158');
        $this->estimate('12', '11:58', 600, 'Oliwa');

        $departures = $this->departures();

        $this->assertCount(1, $departures);
        $this->assertSame('12', $departures[0]->getLine()->getSymbol());
        $this->assertSame('12:08', $this->local($departures[0]->getEstimated()));
    }

    public function testDeparturesAreOrderedByDepartureTime(): void
    {
        $this->scheduled('6', '12:30', 'Łostowice', 'trip-6-1230');
        $this->scheduled('12', '12:10', 'Oliwa', 'trip-12-1210');
        $this->estimate('6', '12:15', 0, 'Łostowice');

        $this->assertSame(
            ['12:10', '12:15', '12:30'],
            array_map(
                fn (Departure $departure) => $this->local($departure->getEstimated() ?? $departure->getScheduled()),
                $this->departures(),
            )
        );
    }

    public function testLimitIsApplied(): void
    {
        $this->scheduled('6', '12:10', 'Łostowice', 'trip-6-1210');
        $this->scheduled('6', '12:20', 'Łostowice', 'trip-6-1220');
        $this->scheduled('6', '12:30', 'Łostowice', 'trip-6-1230');

        $departures = $this->departures(new LimitConstraint(0, 2));

        $this->assertSame(['12:10', '12:20'], array_map(fn (Departure $d) => $this->local($d->getScheduled()), $departures));
    }

    public function testEstimateForLineUnknownToTimetableIsShown(): void
    {
        $this->scheduled('6', '12:20', 'Łostowice', 'trip-6-1220');
        $this->estimate('99', '12:10', 0, 'Zajezdnia');

        $departures = $this->departures();

        $this->assertCount(2, $departures);
        $this->assertSame('99', $departures[0]->getLine()->getSymbol());
        $this->assertSame('Zajezdnia', $departures[0]->getDisplay());
    }

    public function testTimetableIsShownWhenEstimatesAreUnavailable(): void
    {
        $this->scheduled('6', '12:20', 'Łostowice', 'trip-6-1220');
        EstimatesStream::$unavailable = true;

        $departures = $this->departures();

        $this->assertCount(1, $departures);
        $this->assertNull($departures[0]->getEstimated());
    }

    /**
     * @return list<Departure>
     */
    private function departures(Requirement ...$requirements): array
    {
        return array_values(collect($this->repository()->current([$this->stop], ...$requirements))->all());
    }

    private function repository(): DepartureRepository
    {
        return new RealtimeScheduleDepartureRepository(
            $this->scheduleRepository(),
            new ZtmGdanskRealtimeDepartureRepository(
                $this->lineRepository(),
                self::getContainer()->get(ReferenceFactory::class),
                estimatesUrl: EstimatesStream::URL,
            ),
            new MockClock(Carbon::now()->toDateTimeImmutable()),
        );
    }

    private function scheduled(string $line, string $time, string $destination, string $trip): void
    {
        $departure = Carbon::parse('2026-10-20 ' . $time, self::ZONE)->tz('UTC');

        $this->schedule[] = ScheduledStop::createFromArray([
            'order'     => 1,
            'stop'      => $this->stop,
            'arrival'   => $departure,
            'departure' => $departure,
            'track'     => Track::createFromArray([
                'id'          => $line . '-1',
                'line'        => $this->lines[$line],
                'destination' => Stop::createFromArray(['id' => 'end-' . $line,
                    'name'                                   => $destination]),
            ]),
            'trip'      => Trip::reference($trip),
        ]);
    }

    private function estimate(string $line, string $time, int $delay, string $headsign): void
    {
        EstimatesStream::$estimates[$this->stop->getId()][] = [
            'routeId'         => (int) $line,
            'theoreticalTime' => $time,
            'delayInSeconds'  => $delay,
            'headsign'        => $headsign,
            'vehicleCode'     => 1,
        ];
    }

    private function local(?Carbon $time): ?string
    {
        return $time?->clone()->tz(self::ZONE)->format('H:i');
    }

    private function lineRepository(): LineRepository
    {
        $lines = $this->lines;

        return new class($lines) implements LineRepository {
            public function __construct(
                private readonly array $lines
            ) {
            }

            #[\Override]
            public function first(Requirement ...$requirements)
            {
                return $this->all(...$requirements)->first();
            }

            #[\Override]
            public function all(Requirement ...$requirements): Collection
            {
                $ids = [];
                foreach ($requirements as $requirement) {
                    if ($requirement instanceof IdConstraint) {
                        $ids = array_map('strval', (array) $requirement->getId());
                    }
                }

                return collect($this->lines)->only($ids)->values();
            }
        };
    }

    private function scheduleRepository(): ScheduleRepository
    {
        $schedule = $this->schedule;

        return new class($schedule) implements ScheduleRepository {
            public function __construct(
                private readonly array $schedule
            ) {
            }

            #[\Override]
            public function getDeparturesForStop(Stop $stop, Carbon $from, int $count = self::DEFAULT_DEPARTURES_COUNT): Collection
            {
                return collect();
            }

            #[\Override]
            public function first(Requirement ...$requirements)
            {
                return $this->all(...$requirements)->first();
            }

            #[\Override]
            public function all(Requirement ...$requirements): Collection
            {
                return collect($this->schedule);
            }
        };
    }
}
