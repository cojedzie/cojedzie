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

namespace App\Tests\Behat\Support;

use App\Entity\ImportEntity;
use App\Entity\LineEntity;
use App\Entity\OperatorEntity;
use App\Entity\ProviderEntity;
use App\Entity\StopEntity;
use App\Entity\TrackEntity;
use App\Entity\TrackStopEntity;
use App\Entity\TripEntity;
use App\Entity\TripStopEntity;
use App\Service\IdUtils;
use App\Tests\Behat\Support\Provider\TestProvider;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Builds the database data of the fake "test" provider from scenario tables.
 */
final class TestData
{
    private ?ImportEntity $import     = null;
    private ?ProviderEntity $provider = null;
    private ?OperatorEntity $operator = null;

    /**
     * @var array<string, LineEntity>
     */
    private array $lines = [];

    /**
     * @var array<string, StopEntity>
     */
    private array $stops = [];
    private int $tracks  = 0;
    private int $trips   = 0;

    /**
     * @var array<string, TrackEntity> tracks of departures, by line, stop and destination
     */
    private array $routes = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly IdUtils $ids,
    ) {
    }

    public function stop(string $id, string $name, ?string $variant = null): StopEntity
    {
        $stop = new StopEntity();
        $stop->fill([
            'id'      => $this->ids->generate(TestProvider::IDENTIFIER, $id),
            'name'    => $name,
            'variant' => $variant,
            'group'   => $name,
        ]);

        return $this->stops[$id] = $this->persist($stop);
    }

    public function line(string $symbol, string $type = 'bus'): LineEntity
    {
        if (isset($this->lines[$symbol])) {
            return $this->lines[$symbol];
        }

        $line = new LineEntity();
        $line->fill([
            'id'       => $this->ids->generate(TestProvider::IDENTIFIER, $symbol),
            'symbol'   => $symbol,
            'type'     => $type,
            'operator' => $this->operator(),
        ]);

        return $this->lines[$symbol] = $this->persist($line);
    }

    /**
     * @param string[] $stopIds stops in the order the line visits them
     */
    public function track(string $lineSymbol, array $stopIds): TrackEntity
    {
        $track = new TrackEntity();
        $track->fill([
            'id'   => $this->ids->generate(TestProvider::IDENTIFIER, sprintf('%s-%d', $lineSymbol, ++$this->tracks)),
            'line' => $this->line($lineSymbol),
        ]);
        $this->persist($track);

        $stopsInTrack = [];
        foreach (array_values($stopIds) as $order => $stopId) {
            $stopInTrack = new TrackStopEntity();
            $stopInTrack->fill([
                'stop'  => $this->stops[$stopId] ?? throw new \InvalidArgumentException("Unknown stop '$stopId'."),
                'track' => $track,
                'order' => $order + 1,
            ]);
            $stopInTrack->setImport($this->import());

            $this->em->persist($stopInTrack);
            $stopsInTrack[] = $stopInTrack;
        }

        $track->setStopsInTrack($stopsInTrack);

        return $track;
    }

    /**
     * A timetable entry: a trip of the line leaving the stop at the given time towards a destination stop named
     * like the departure's display.
     */
    public function trip(string $lineSymbol, string $stopId, Carbon $departure, string $display): TripEntity
    {
        $destination = 'destination-' . new AsciiSlugger()->slug($display)->lower();
        if (!isset($this->stops[$destination])) {
            $this->stop($destination, $display);
        }

        $route = sprintf('%s|%s|%s', $lineSymbol, $stopId, $destination);
        $track = $this->routes[$route] ??= $this->track($lineSymbol, [$stopId, $destination]);

        $trip = new TripEntity();
        $trip->fill([
            'id'       => $this->ids->generate(TestProvider::IDENTIFIER, sprintf('trip-%d', ++$this->trips)),
            'track'    => $track,
            'operator' => $this->operator(),
        ]);
        $this->persist($trip);

        $times = [$departure, $departure->clone()->addMinutes(5)];
        $stops = [];
        foreach ([$stopId, $destination] as $order => $id) {
            $stop = new TripStopEntity();
            $stop->fill([
                'stop'      => $this->stops[$id] ?? throw new \InvalidArgumentException("Unknown stop '$id'."),
                'trip'      => $trip,
                'order'     => $order + 1,
                'arrival'   => $times[$order]->clone()->tz('UTC'),
                'departure' => $times[$order]->clone()->tz('UTC'),
            ]);
            $stop->setImport($this->import());

            $this->em->persist($stop);
            $stops[] = $stop;
        }
        $trip->setStops($stops);

        return $trip;
    }

    public function flush(): void
    {
        $this->em->flush();
    }

    private function persist(object $entity): object
    {
        $entity->setProvider($this->provider());
        $entity->setImport($this->import());

        $this->em->persist($entity);

        return $entity;
    }

    private function import(): ImportEntity
    {
        if ($this->import === null) {
            $this->import = new ImportEntity();
            $this->import->setStartedAt(Carbon::now());
            $this->em->persist($this->import);
        }

        return $this->import;
    }

    private function provider(): ProviderEntity
    {
        if ($this->provider === null) {
            $this->provider = $this->em->find(ProviderEntity::class, TestProvider::IDENTIFIER) ?? new ProviderEntity();
            $this->provider->fill([
                'id'     => TestProvider::IDENTIFIER,
                'name'   => TestProvider::NAME,
                'class'  => TestProvider::class,
                'import' => $this->import(),
            ]);
            $this->em->persist($this->provider);
        }

        return $this->provider;
    }

    private function operator(): OperatorEntity
    {
        if ($this->operator === null) {
            $this->operator = new OperatorEntity();
            $this->operator->fill([
                'id'   => $this->ids->generate(TestProvider::IDENTIFIER, 'operator'),
                'name' => 'Test Operator',
            ]);
            $this->persist($this->operator);
        }

        return $this->operator;
    }
}
