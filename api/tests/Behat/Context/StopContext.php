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
use App\Tests\Behat\Support\Provider\TestProvider;
use App\Tests\Behat\Support\TestData;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;

final class StopContext implements Context
{
    public function __construct(
        private readonly TestData $data,
        private readonly ApiClient $client,
    ) {
    }

    #[Given('there are stops:')]
    public function thereAreStops(TableNode $stops): void
    {
        foreach ($stops->getHash() as $stop) {
            $this->data->stop($stop['id'], $stop['name'], ($stop['variant'] ?? '') ?: null);
        }

        $this->data->flush();
    }

    #[Given('line :symbol runs through :stops')]
    public function lineRunsThrough(string $symbol, string $stops): void
    {
        $this->data->track($symbol, self::split($stops));
        $this->data->flush();
    }

    #[When('I search for stops named :name')]
    public function iSearchForStopsNamed(string $name): void
    {
        $this->search(['name' => $name]);
    }

    #[When('I search for stops named :name with destinations')]
    public function iSearchForStopsNamedWithDestinations(string $name): void
    {
        $this->search(['name' => $name,
            'embed'           => 'destinations']);
    }

    #[When('I search for stops whose name begins with :prefix')]
    public function iSearchForStopsWhoseNameBeginsWith(string $prefix): void
    {
        $this->search(['name:begins' => $prefix]);
    }

    #[Then('I should see stop groups:')]
    public function iShouldSeeStopGroups(TableNode $expected): void
    {
        $actual = array_map(
            fn (array $group) => [
                'name'  => $group['name'],
                'stops' => implode(', ', array_column($group['stops'], 'id')),
            ],
            $this->groups(),
        );

        Assert::assertSame($expected->getHash(), $actual, self::describe($actual));
    }

    #[Then('I should see no stop groups')]
    public function iShouldSeeNoStopGroups(): void
    {
        Assert::assertSame([], $this->groups());
    }

    #[Then('stop :id should have destinations:')]
    public function stopShouldHaveDestinations(string $id, TableNode $expected): void
    {
        $actual = array_map(
            fn (array $destination) => [
                'destination' => $destination['stop']['id'],
                'lines'       => implode(', ', array_column($destination['lines'], 'symbol')),
            ],
            $this->stop($id)['destinations'] ?? [],
        );

        Assert::assertSame($expected->getHash(), $actual, self::describe($actual));
    }

    #[Then('stop :id should have no destinations')]
    public function stopShouldHaveNoDestinations(string $id): void
    {
        Assert::assertSame([], $this->stop($id)['destinations'] ?? []);
    }

    private static function describe(array $actual): string
    {
        return 'Actual: ' . json_encode($actual, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * @return string[]
     */
    private static function split(string $list): array
    {
        return array_map(trim(...), explode(',', $list));
    }

    private function search(array $query): void
    {
        $this->client->get(sprintf('/api/v1/%s/stop-groups', TestProvider::IDENTIFIER), $query);
    }

    private function groups(): array
    {
        Assert::assertSame(200, $this->client->lastResponse()->getStatusCode(), (string) $this->client->lastResponse()->getContent());

        return $this->client->json();
    }

    private function stop(string $id): array
    {
        foreach ($this->groups() as $group) {
            foreach ($group['stops'] as $stop) {
                if ($stop['id'] === $id) {
                    return $stop;
                }
            }
        }

        Assert::fail("Stop '$id' is not in the response.");
    }
}
