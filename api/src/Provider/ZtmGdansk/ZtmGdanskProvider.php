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
use App\Provider\Database\RealtimeScheduleDepartureRepository;
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
use Psr\Clock\ClockInterface;

class ZtmGdanskProvider implements Provider
{
    final public const string IDENTIFIER = 'trojmiasto';

    /**
     * Time zone of the ZTM Gdańsk feeds: their data is keyed by local dates.
     */
    final public const string TIMEZONE = 'Europe/Warsaw';
    private $lines;
    private $departures;
    private $stops;
    private $tracks;
    private $trips;
    private ProviderEntity $entity;

    /**
     * Today's date in the feeds' time zone, which selects the day of a feed to import.
     */
    public static function feedDate(ClockInterface $clock): string
    {
        return $clock->now()->setTimezone(new \DateTimeZone(self::TIMEZONE))->format('Y-m-d');
    }

    #[\Override]
    public function getName(): string
    {
        return 'MZKZG - Trójmiasto';
    }

    #[\Override]
    public function getShortName(): string
    {
        return 'Trójmiasto';
    }

    #[\Override]
    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    #[\Override]
    public function getAttribution(): string
    {
        return '<a href="http://ztm.gda.pl/otwarty_ztm">Otwarte Dane</a> Zarządu Transportu Miejskiego w Gdańsku';
    }

    #[\Override]
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
        ReferenceFactory $referenceFactory,
        ClockInterface $clock,
        ZtmGdanskConfig $config,
    ) {
        $provider = $this->refreshProviderEntity();

        $lines    = $lines->withProvider($provider);
        $stops    = $stops->withProvider($provider);
        $tracks   = $tracks->withProvider($provider);
        $schedule = $schedule->withProvider($provider);
        $trips    = $trips->withProvider($provider);

        $this->lines      = $lines;
        $this->departures = new RealtimeScheduleDepartureRepository(
            $schedule,
            new ZtmGdanskRealtimeDepartureRepository($lines, $referenceFactory, $config),
            $clock,
        );
        $this->stops  = $stops;
        $this->tracks = $tracks;
        $this->entity = $provider;
        $this->trips  = $trips;

        $this->messages->setProvider($provider);
    }

    #[\Override]
    public function getDepartureRepository(): DepartureRepository
    {
        return $this->departures;
    }

    #[\Override]
    public function getLineRepository(): LineRepository
    {
        return $this->lines;
    }

    #[\Override]
    public function getStopRepository(): StopRepository
    {
        return $this->stops;
    }

    #[\Override]
    public function getMessageRepository(): MessageRepository
    {
        return $this->messages;
    }

    #[\Override]
    public function getTrackRepository(): TrackRepository
    {
        return $this->tracks;
    }

    #[\Override]
    public function getTripRepository(): TripRepository
    {
        return $this->trips;
    }

    #[\Override]
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
