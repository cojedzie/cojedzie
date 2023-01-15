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

namespace App\Provider\ZtmGdansk;

use App\Dto\Location;
use App\Entity\ProviderEntity;
use App\Provider\Database\GenericLineRepository;
use App\Provider\Database\GenericScheduleRepository;
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
use App\Service\Proxy\ReferenceFactory;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;

class ZtmGdanskProvider implements Provider
{
    final public const BASE_URL   = 'https://ckan.multimediagdansk.pl/dataset/c24aa637-3619-4dc2-a171-a23eec8f2172/resource';
    final public const IDENTIFIER = 'trojmiasto';
    private $lines;
    private $departures;
    private $stops;
    private $tracks;
    private $trips;
    private ProviderEntity $entity;

    public function getName(): string
    {
        return 'MZKZG - Trójmiasto';
    }

    public function getShortName(): string
    {
        return 'Trójmiasto';
    }

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public function getAttribution(): string
    {
        return '<a href="http://ztm.gda.pl/otwarty_ztm">Otwarte Dane</a> Zarządu Transportu Miejskiego w Gdańsku';
    }

    public function getLocation(): Location
    {
        return new Location(18.6466, 54.3520);
    }

    public function __construct(
        private readonly EntityManagerInterface $em,
        GenericLineRepository $lines,
        GenericStopRepository $stops,
        GenericTrackRepository $tracks,
        GenericScheduleRepository $schedule,
        GenericTripRepository $trips,
        private readonly ZtmGdanskMessageRepository $messages,
        ReferenceFactory $referenceFactory
    ) {
        $provider = $this->refreshProviderEntity();

        $lines    = $lines->withProvider($provider);
        $stops    = $stops->withProvider($provider);
        $tracks   = $tracks->withProvider($provider);
        $schedule = $schedule->withProvider($provider);
        $trips    = $trips->withProvider($provider);

        $this->lines      = $lines;
        $this->departures = new ZtmGdanskDepartureRepository($lines, $schedule, $referenceFactory);
        $this->stops      = $stops;
        $this->tracks     = $tracks;
        $this->entity     = $provider;
        $this->trips      = $trips;
    }

    public function getDepartureRepository(): DepartureRepository
    {
        return $this->departures;
    }

    public function getLineRepository(): LineRepository
    {
        return $this->lines;
    }

    public function getStopRepository(): StopRepository
    {
        return $this->stops;
    }

    public function getMessageRepository(): MessageRepository
    {
        return $this->messages;
    }

    public function getTrackRepository(): TrackRepository
    {
        return $this->tracks;
    }

    public function getTripRepository(): TripRepository
    {
        return $this->trips;
    }

    public function getLastUpdate(): ?Carbon
    {
        $this->refreshProviderEntity();

        return $this->entity->getUpdateDate();
    }

    private function refreshProviderEntity(): ProviderEntity
    {
        return $this->entity = $this->em->getReference(ProviderEntity::class, $this->getIdentifier());
    }
}
