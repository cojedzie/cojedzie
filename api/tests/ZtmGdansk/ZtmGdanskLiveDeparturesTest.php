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

use App\Provider\Live\DepartureStatus;
use App\Provider\Live\LiveDeparture;
use App\Provider\Live\LiveDepartureException;
use App\Provider\ZtmGdansk\ZtmGdanskConfig;
use App\Provider\ZtmGdansk\ZtmGdanskLiveDepartures;
use Kadet\Functional\Collections as c;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ZtmGdanskLiveDeparturesTest extends TestCase
{
    private const string API = 'https://ztm.test';

    private const string RESOURCES = __DIR__ . '/Resources/live';

    /**
     * @var list<string>
     */
    private array $urls = [];

    public function testAllStopsAreReadInOneRequest(): void
    {
        $departures = $this->live(file_get_contents(self::RESOURCES . '/all-stops.json'))->all();

        $this->assertSame([self::API . '/departures'], $this->urls);
        $this->assertSame(['8227', '114'], $departures |> c\map(fn (LiveDeparture $departure) => $departure->stopId) |> c\toArray());
    }

    public function testRealtimeEntryHasAnEstimate(): void
    {
        $departure = $this->departureFrom($this->live(file_get_contents(self::RESOURCES . '/all-stops.json'))->all(), '8227');

        $this->assertSame('171', $departure->lineId);
        $this->assertSame('171', $departure->lineSymbol);
        $this->assertSame('12@2026-11-01T11:08:00Z', $departure->tripKey);
        $this->assertEquals(new \DateTimeImmutable('2026-11-01T11:13:00Z'), $departure->scheduledAt);
        $this->assertEquals(new \DateTimeImmutable('2026-11-01T11:12:03Z'), $departure->estimatedAt);
        $this->assertSame(DepartureStatus::Realtime, $departure->status);
        $this->assertSame('Oliwa', $departure->headsign);
        $this->assertSame('9221', $departure->vehicle);
        $this->assertSame('171-01', $departure->duty);
    }

    public function testTimetableOnlyEntryHasNoEstimate(): void
    {
        $departure = $this->departureFrom($this->live(file_get_contents(self::RESOURCES . '/all-stops.json'))->all(), '114');

        $this->assertSame('910', $departure->lineId);
        $this->assertSame('C10', $departure->lineSymbol);
        $this->assertSame(DepartureStatus::Scheduled, $departure->status);
        $this->assertNull($departure->estimatedAt);
        $this->assertNull($departure->vehicle);
        // the duty is planned, so it's known before a vehicle is assigned
        $this->assertSame('910-04', $departure->duty);
        $this->assertSame('Cmentarz Łostowicki', $departure->headsign);
    }

    public function testOneStopIsAskedForByItsId(): void
    {
        $all   = json_decode(file_get_contents(self::RESOURCES . '/all-stops.json'), true);
        $board = $this->live(json_encode($all['8227']))->forStop('8227');

        $this->assertSame([self::API . '/departures?stopId=8227'], $this->urls);
        $this->assertCount(1, $board);
        $this->assertSame('8227', $board->first()->stopId);
    }

    public function testGarbageResponseThrows(): void
    {
        $this->expectException(LiveDepartureException::class);

        $this->live(file_get_contents(self::RESOURCES . '/garbage.html'))->all();
    }

    public function testFailedRequestThrows(): void
    {
        $this->expectException(LiveDepartureException::class);

        $this->live(new MockResponse('Bad Gateway', ['http_code' => 502]))->all();
    }

    public function testUnreachableApiThrows(): void
    {
        $this->expectException(LiveDepartureException::class);

        $this->live(new MockResponse('', ['error' => 'Connection refused']))->forStop('8227');
    }

    private function live(string|MockResponse $response): ZtmGdanskLiveDepartures
    {
        $http = new MockHttpClient(function (string $method, string $url) use ($response) {
            $this->urls[] = $url;

            return $response instanceof MockResponse ? $response : new MockResponse($response);
        });

        return new ZtmGdanskLiveDepartures($http, new ZtmGdanskConfig(self::API, '', '', '', '', '', ''));
    }

    /**
     * @param iterable<LiveDeparture> $departures
     */
    private function departureFrom(iterable $departures, string $stop): LiveDeparture
    {
        $departure = $departures |> c\first(fn (LiveDeparture $departure) => $departure->stopId === $stop);
        $this->assertInstanceOf(LiveDeparture::class, $departure);

        return $departure;
    }
}
