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

namespace App\Tests\Behat\Context;

use App\DepartureHistory\DepartureHistory;
use App\Provider\Live\DepartureStatus;
use App\Provider\Live\LiveDeparture;
use App\Tests\Behat\Support\ApiClient;
use App\Tests\Behat\Support\FrozenTime;
use App\Tests\Behat\Support\Provider\TestProvider;
use App\Tests\Behat\Support\TestData;
use App\Tests\DepartureHistory\Support\InMemoryDepartureHistory;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;

final readonly class TimetableContext implements Context
{
    public function __construct(
        private TestData $data,
        private FrozenTime $time,
        private ApiClient $client,
        private DepartureHistory $history,
    ) {
    }

    #[BeforeScenario]
    public function clearHistory(): void
    {
        $this->history()->recorded = [];
    }

    #[Given('there are timetable entries:')]
    public function thereAreTimetableEntries(TableNode $entries): void
    {
        foreach ($entries->getHash() as $entry) {
            $this->data->trip(
                $entry['line'],
                $entry['stop'],
                $this->time->moment($entry['departure']),
                $entry['display'],
                ($entry['service day'] ?? '') ?: null,
            );
        }

        $this->data->flush();
    }

    #[Given('line :line leaves :stop every :minutes minutes from :first to :last on :date')]
    public function lineLeavesEvery(
        string $line,
        string $stop,
        int $minutes,
        string $first,
        string $last,
        string $date,
    ): void {
        $departure = $this->time->moment("$date $first");
        $end       = $this->time->moment("$date $last");

        for (; $departure->lessThanOrEqualTo($end); $departure = $departure->clone()->addMinutes($minutes)) {
            $this->data->trip($line, $stop, $departure, 'Terminus');
        }

        $this->data->flush();
    }

    /**
     * The departure history saw the departure at the time it was observed; the times are local dates and times
     * ("Y-m-d H:i").
     */
    #[Given(
        'the departure history recorded line :line leaving stop :stop scheduled at :scheduled ' .
        'estimated at :estimated observed at :observed',
    )]
    public function theDepartureHistoryRecorded(
        string $line,
        string $stop,
        string $scheduled,
        string $estimated,
        string $observed,
    ): void {
        $scheduledAt = $this->time->moment($scheduled)->toDateTimeImmutable();

        $this->history()->record(TestProvider::IDENTIFIER, $this->time->moment($observed)->toDateTimeImmutable(), [
            new LiveDeparture(
                stopId: $stop,
                lineId: $line,
                lineSymbol: $line,
                tripKey: $scheduled,
                scheduledAt: $scheduledAt,
                estimatedAt: $this->time->moment($estimated)->toDateTimeImmutable(),
                status: DepartureStatus::Realtime,
                headsign: 'Terminus',
                vehicle: null,
                duty: null,
            ),
        ]);
    }

    /**
     * Query values are sent URL-encoded, so offsets like +01:00 arrive intact.
     */
    #[When('I ask for the timetable of :path with query:')]
    public function iAskForTheTimetableOfWithQuery(string $path, TableNode $query): void
    {
        $parameters = [];

        foreach ($query->getHash() as ['name' => $name, 'value' => $value]) {
            $parameters[$name] = $value;
        }

        $this->client->get(sprintf('/api/v1/%s/%s', TestProvider::IDENTIFIER, $path), $parameters);
    }

    #[Then('I should see timetable entries:')]
    public function iShouldSeeTimetableEntries(TableNode $expected): void
    {
        // the state is compared only when the table has a column for it
        $withState = in_array('state', $expected->getRow(0), true);

        $actual = array_map(
            fn (array $departure) => [
                // line and stop are references, which carry only the id
                'line'        => $departure['line']['id'],
                'stop'        => $departure['stop']['id'],
                'scheduled'   => $this->time->localDateTime($departure['scheduled']),
                'service day' => (string) ($departure['serviceDay'] ?? ''),
                ...($withState ? ['state' => $departure['state']] : []),
            ],
            $this->entries(),
        );

        Assert::assertSame(
            $expected->getHash(),
            $actual,
            'Actual: ' . json_encode($actual, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    #[Then('I should see :count timetable entries')]
    public function iShouldSeeTimetableEntriesCount(int $count): void
    {
        Assert::assertCount($count, $this->entries());
    }

    #[Then('the first timetable entry should be line :line at :scheduled')]
    public function theFirstTimetableEntryShouldBe(string $line, string $scheduled): void
    {
        $first = $this->entries()[0] ?? Assert::fail('There is no timetable entry.');

        Assert::assertSame(
            ['line' => $line, 'scheduled' => $scheduled],
            ['line' => $first['line']['id'], 'scheduled' => $this->time->localDateTime($first['scheduled'])],
        );
    }

    #[Then('the timetable entry of line :line at :scheduled should be :state')]
    public function theTimetableEntryShouldBe(string $line, string $scheduled, string $state): void
    {
        Assert::assertSame($state, $this->entry($line, $scheduled)['state']);
    }

    #[Then('the timetable entry of line :line at :scheduled should be estimated at :estimated')]
    public function theTimetableEntryShouldBeEstimatedAt(string $line, string $scheduled, string $estimated): void
    {
        Assert::assertSame(
            $estimated,
            $this->time->localDateTime($this->entry($line, $scheduled)['estimated'] ?? null),
        );
    }

    #[Then('the timetable entry of line :line at :scheduled should have no estimate')]
    public function theTimetableEntryShouldHaveNoEstimate(string $line, string $scheduled): void
    {
        Assert::assertNull($this->entry($line, $scheduled)['estimated'] ?? null);
    }

    private function history(): InMemoryDepartureHistory
    {
        return $this->history instanceof InMemoryDepartureHistory
            ? $this->history
            : throw new \LogicException('The test environment records the departure history in memory.');
    }

    private function entry(string $line, string $scheduled): array
    {
        foreach ($this->entries() as $entry) {
            if ($entry['line']['id'] === $line && $this->time->localDateTime($entry['scheduled']) === $scheduled) {
                return $entry;
            }
        }

        Assert::fail(sprintf('There is no timetable entry of line %s at %s.', $line, $scheduled));
    }

    private function entries(): array
    {
        $response = $this->client->lastResponse();
        Assert::assertSame(200, $response->getStatusCode(), mb_substr((string) $response->getContent(), 0, 300));

        return $this->client->json();
    }
}
