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

namespace App\Tests\Behat\Support\Provider;

use App\DataConverter\Converter;
use App\Dto\Departure;
use App\Dto\Dto;
use App\Dto\Stop;
use App\Entity\LineEntity;
use App\Entity\StopEntity;
use App\Filter\Requirement\LimitConstraint;
use App\Filter\Requirement\Requirement;
use App\Provider\DepartureRepository;
use App\Service\IdUtils;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * Departures given by scenarios. Implements the behaviour every provider's departure repository must have:
 * departures of the given stops, merged, ordered by departure time, without the ones that already left.
 */
final class InMemoryDepartureRepository implements DepartureRepository
{
    /**
     * @var list<array{stop: string, line: string, scheduled: Carbon, estimated: ?Carbon, display: ?string}>
     */
    private array $departures = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Converter $converter,
        private readonly IdUtils $ids,
        private readonly ClockInterface $clock,
    ) {
    }

    public function add(string $stopId, string $lineSymbol, Carbon $scheduled, ?Carbon $estimated, ?string $display): void
    {
        $this->departures[] = [
            'stop'      => $stopId,
            'line'      => $lineSymbol,
            'scheduled' => $scheduled,
            'estimated' => $estimated,
            'display'   => $display,
        ];
    }

    public function clear(): void
    {
        $this->departures = [];
    }

    #[\Override]
    public function current(iterable $stops, Requirement ...$requirements)
    {
        $stopIds = collect($stops)->map(fn (Stop $stop) => $stop->getId())->unique()->all();
        $now     = Carbon::instance($this->clock->now());

        $departures = collect($this->departures)
            ->filter(fn (array $departure) => in_array($departure['stop'], $stopIds, true))
            ->map($this->createDeparture(...))
            ->filter(fn (Departure $departure) => $departure->getDeparture()->greaterThan($now))
            ->sortBy(fn (Departure $departure) => $departure->getDeparture()->getTimestamp())
            ->values();

        foreach ($requirements as $requirement) {
            if ($requirement instanceof LimitConstraint) {
                $departures = $departures->slice($requirement->getOffset(), $requirement->getCount())->values();
            }
        }

        return $departures;
    }

    private function createDeparture(array $departure): Departure
    {
        $line = $this->em->find(LineEntity::class, $this->ids->generate(TestProvider::IDENTIFIER, $departure['line']));
        $stop = $this->em->find(StopEntity::class, $this->ids->generate(TestProvider::IDENTIFIER, $departure['stop']));

        return Departure::createFromArray([
            'key'       => sprintf('%s::%s', $departure['line'], $departure['scheduled']->format('H:i')),
            'line'      => $this->converter->convert($line, Dto::class),
            'stop'      => $this->converter->convert($stop, Dto::class),
            'scheduled' => $departure['scheduled']->clone()->tz('UTC'),
            'estimated' => $departure['estimated']?->clone()->tz('UTC'),
            'display'   => $departure['display'],
        ]);
    }
}
