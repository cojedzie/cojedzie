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

use App\Dto\Location;
use App\Entity\ProviderEntity;
use App\Filter\Requirement\Requirement;
use App\Provider\Database\GenericLineRepository;
use App\Provider\Database\GenericStopRepository;
use App\Provider\Database\GenericTrackRepository;
use App\Provider\Database\GenericTripRepository;
use App\Provider\DepartureRepository;
use App\Provider\LineRepository;
use App\Provider\MessageRepository;
use App\Provider\Provider;
use App\Provider\StopRepository;
use App\Provider\TrackRepository;
use App\Provider\TripRepository;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Support\Collection;

/**
 * Provider used only by behavioural tests; its data is defined by scenarios.
 */
final class TestProvider implements Provider
{
    public const string IDENTIFIER = 'test';
    public const string NAME       = 'Test City';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly GenericLineRepository $lines,
        private readonly GenericStopRepository $stops,
        private readonly GenericTrackRepository $tracks,
        private readonly GenericTripRepository $trips,
        private readonly InMemoryDepartureRepository $departures,
    ) {
    }

    #[\Override]
    public function getDepartureRepository(): DepartureRepository
    {
        return $this->departures;
    }

    #[\Override]
    public function getLineRepository(): LineRepository
    {
        return $this->lines->withProvider($this->entity());
    }

    #[\Override]
    public function getStopRepository(): StopRepository
    {
        return $this->stops->withProvider($this->entity());
    }

    #[\Override]
    public function getMessageRepository(): MessageRepository
    {
        return new class() implements MessageRepository {
            #[\Override]
            public function first(Requirement ...$requirements)
            {
                return null;
            }

            #[\Override]
            public function all(Requirement ...$requirements): Collection
            {
                return collect();
            }
        };
    }

    #[\Override]
    public function getTrackRepository(): TrackRepository
    {
        return $this->tracks->withProvider($this->entity());
    }

    #[\Override]
    public function getTripRepository(): TripRepository
    {
        return $this->trips->withProvider($this->entity());
    }

    #[\Override]
    public function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getShortName(): string
    {
        return 'Test';
    }

    #[\Override]
    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    #[\Override]
    public function getAttribution(): ?string
    {
        return null;
    }

    #[\Override]
    public function getLocation(): Location
    {
        return new Location(18.6466, 54.3520);
    }

    #[\Override]
    public function getLastUpdate(): ?Carbon
    {
        return null;
    }

    private function entity(): ProviderEntity
    {
        // looked up on every call, the database is rolled back after each scenario
        return $this->em->getReference(ProviderEntity::class, self::IDENTIFIER);
    }
}
