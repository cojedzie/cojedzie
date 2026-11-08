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

use App\Tests\Behat\Support\ApiClient;
use App\Tests\Behat\Support\FrozenTime;
use App\Tests\Behat\Support\Provider\TestProvider;
use App\Tests\Behat\Support\TestData;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
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
    ) {
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
    public function lineLeavesEvery(string $line, string $stop, int $minutes, string $first, string $last, string $date): void
    {
        $departure = $this->time->moment("$date $first");
        $end       = $this->time->moment("$date $last");

        for (; $departure->lessThanOrEqualTo($end); $departure = $departure->clone()->addMinutes($minutes)) {
            $this->data->trip($line, $stop, $departure, 'Terminus');
        }

        $this->data->flush();
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
        $actual = array_map(
            fn (array $departure) => [
                // line and stop are references, which carry only the id
                'line'        => $departure['line']['id'],
                'stop'        => $departure['stop']['id'],
                'scheduled'   => $this->time->localDateTime($departure['scheduled']),
                'service day' => (string) ($departure['serviceDay'] ?? ''),
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

    private function entries(): array
    {
        $response = $this->client->lastResponse();
        Assert::assertSame(200, $response->getStatusCode(), mb_substr((string) $response->getContent(), 0, 300));

        return $this->client->json();
    }
}
