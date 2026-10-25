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
use App\Tests\Behat\Support\Provider\InMemoryDepartureRepository;
use App\Tests\Behat\Support\Provider\TestProvider;
use App\Tests\Behat\Support\TestData;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;

final class DepartureContext implements Context
{
    public function __construct(
        private readonly InMemoryDepartureRepository $departures,
        private readonly TestData $data,
        private readonly FrozenTime $time,
        private readonly ApiClient $client,
    ) {
    }

    #[BeforeScenario]
    public function clearDepartures(): void
    {
        $this->departures->clear();
    }

    #[Given('there are departures:')]
    public function thereAreDepartures(TableNode $departures): void
    {
        foreach ($departures->getHash() as $departure) {
            $this->data->trip($departure['line'], $departure['stop'], $this->time->at($departure['scheduled']), $departure['display']);

            if ($departure['estimated'] !== '') {
                $this->live($departure, ($departure['live display'] ?? '') ?: $departure['display']);
            }
        }

        $this->data->flush();
    }

    #[Given('there are live departures not in the timetable:')]
    public function thereAreLiveDeparturesNotInTheTimetable(TableNode $departures): void
    {
        foreach ($departures->getHash() as $departure) {
            $this->data->line($departure['line']);
            $this->live($departure, $departure['display']);
        }

        $this->data->flush();
    }

    #[When('I ask for departures from :stops')]
    public function iAskForDeparturesFrom(string $stops): void
    {
        $this->request($stops);
    }

    #[When('I ask for at most :limit departures from :stops')]
    public function iAskForAtMostDeparturesFrom(int $limit, string $stops): void
    {
        $this->request($stops, ['limit' => $limit]);
    }

    #[Then('I should see departures:')]
    public function iShouldSeeDepartures(TableNode $expected): void
    {
        $actual = array_map(
            fn (array $departure) => [
                'line'      => $departure['line']['symbol'],
                'stop'      => $departure['stop']['id'],
                'scheduled' => $this->time->local($departure['scheduled']),
                'estimated' => $this->time->local($departure['estimated'] ?? null),
                'delay'     => isset($departure['delay']) ? (string) $departure['delay'] : '',
            ],
            $this->departures(),
        );

        Assert::assertSame(
            $expected->getHash(),
            $actual,
            'Actual: ' . json_encode($actual, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    #[Then('the departures should go to:')]
    public function theDeparturesShouldGoTo(TableNode $expected): void
    {
        $actual = array_map(
            fn (array $departure) => ['line' => $departure['line']['symbol'],
                'display'                    => $departure['display']],
            $this->departures(),
        );

        Assert::assertSame($expected->getHash(), $actual, 'Actual: ' . json_encode($actual, JSON_UNESCAPED_UNICODE));
    }

    #[Then('I should see no departures')]
    public function iShouldSeeNoDepartures(): void
    {
        Assert::assertSame([], $this->departures());
    }

    private function live(array $departure, string $display): void
    {
        $this->departures->add(
            $departure['stop'],
            $departure['line'],
            $this->time->at($departure['scheduled']),
            $this->time->at($departure['estimated']),
            $display,
        );
    }

    private function request(string $stops, array $query = []): void
    {
        $this->client->get(sprintf('/api/v1/%s/departures', TestProvider::IDENTIFIER), [
            ...$query,
            'stop' => array_map(trim(...), explode(',', $stops)),
        ]);
    }

    private function departures(): array
    {
        $response = $this->client->lastResponse();
        Assert::assertSame(200, $response->getStatusCode(), mb_substr((string) $response->getContent(), 0, 300));

        return $this->client->json();
    }
}
