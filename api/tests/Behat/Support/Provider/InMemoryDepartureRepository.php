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
use App\Filter\Requirement\Requirement;
use App\Provider\DepartureRepository;
use App\Service\IdUtils;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Live departures given by scenarios: what a realtime source reports for each requested stop. Joining them with the
 * timetable, ordering and filtering is left to RealtimeScheduleDepartureRepository, as for real providers.
 */
final class InMemoryDepartureRepository implements DepartureRepository
{
    /**
     * @var list<array{stop: string, line: string, scheduled: Carbon, estimated: Carbon, display: ?string}>
     */
    private array $departures = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Converter $converter,
        private readonly IdUtils $ids,
    ) {
    }

    public function add(string $stopId, string $lineSymbol, Carbon $scheduled, Carbon $estimated, ?string $display): void
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
        return collect($stops)
            ->flatMap(fn (Stop $stop) => collect($this->departures)
                ->where('stop', $stop->getId())
                ->map(fn (array $departure) => $this->createDeparture($departure, $stop)))
            ->values();
    }

    private function createDeparture(array $departure, Stop $stop): Departure
    {
        $line = $this->em->find(LineEntity::class, $this->ids->generate(TestProvider::IDENTIFIER, $departure['line']));

        return Departure::createFromArray([
            'key'       => sprintf('%s::%s', $departure['line'], $departure['scheduled']->format('H:i')),
            'line'      => $this->converter->convert($line, Dto::class),
            'stop'      => $stop,
            'scheduled' => $departure['scheduled']->clone()->tz('UTC'),
            'estimated' => $departure['estimated']->clone()->tz('UTC'),
            'display'   => $departure['display'],
        ]);
    }
}
