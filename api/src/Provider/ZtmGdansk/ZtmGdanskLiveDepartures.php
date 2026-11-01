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

namespace App\Provider\ZtmGdansk;

use App\Provider\Live\DepartureStatus;
use App\Provider\Live\LiveDeparture;
use App\Provider\Live\LiveDepartureException;
use App\Provider\Live\LiveDepartureSource;
use Ds\Seq;
use Kadet\Functional\Collections as c;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Live departures from ZTM Gdańsk's TRISTAR departures API. With a stopId it answers for that stop, without one for
 * every stop at once, as an object keyed by stop id.
 */
final readonly class ZtmGdanskLiveDepartures implements LiveDepartureSource
{
    private const string DEPARTURES_PATH = '/departures';

    public function __construct(
        private HttpClientInterface $http,
        private ZtmGdanskConfig $config,
    ) {
    }

    #[\Override]
    public function getProviderIdentifier(): string
    {
        return ZtmGdanskProvider::IDENTIFIER;
    }

    #[\Override]
    public function all(): Seq
    {
        $stops = $this->request([]);

        return new Seq(array_keys($stops))
            |> c\flatMap(fn (int|string $stop) => $this->board((string) $stop, $stops[$stop]));
    }

    /**
     * @return Seq<LiveDeparture>
     *
     * @throws LiveDepartureException
     */
    public function forStop(string $stopId): Seq
    {
        return $this->board($stopId, $this->request(['stopId' => $stopId]));
    }

    /**
     * @param array<string, string> $query
     *
     * @return array<mixed>
     */
    private function request(array $query): array
    {
        $url = $this->config->apiUrl . self::DEPARTURES_PATH;

        try {
            return $this->http->request('GET', $url, ['query' => $query])->toArray();
        } catch (ExceptionInterface $exception) {
            throw new LiveDepartureException(sprintf('ZTM departures at %s can\'t be read: %s', $url, $exception->getMessage()), previous: $exception);
        }
    }

    /**
     * @param array<string, mixed> $board one stop's answer: lastUpdate and departures
     *
     * @return Seq<LiveDeparture>
     */
    private function board(string $stopId, array $board): Seq
    {
        return new Seq($board['departures'] ?? [])
            |> c\map(fn (array $departure) => $this->departure($stopId, $departure));
    }

    /**
     * @param array<string, mixed> $departure
     */
    private function departure(string $stopId, array $departure): LiveDeparture
    {
        // SCHEDULED entries come from the API's own timetable, REALTIME ones without a delay have no estimate either
        $realtime = ($departure['status'] ?? null) === 'REALTIME' && $departure['delayInSeconds'] !== null;

        return new LiveDeparture(
            stopId: $stopId,
            lineId: (string) $departure['routeId'],
            lineSymbol: (string) ($departure['routeShortName'] ?? $departure['routeId']),
            tripKey: sprintf('%s@%s', $departure['tripId'], $departure['scheduledTripStartTime']),
            scheduledAt: new \DateTimeImmutable($departure['theoreticalTime']),
            estimatedAt: $realtime ? new \DateTimeImmutable($departure['estimatedTime']) : null,
            status: $realtime ? DepartureStatus::Realtime : DepartureStatus::Scheduled,
            headsign: trim((string) $departure['headsign']),
            vehicle: $departure['vehicleCode'] !== null ? (string) $departure['vehicleCode'] : null,
            duty: isset($departure['vehicleService']) ? (string) $departure['vehicleService'] : null,
        );
    }
}
