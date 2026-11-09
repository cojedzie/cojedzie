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

final readonly class LineContext implements Context
{
    public function __construct(
        private TestData $data,
        private ApiClient $client,
    ) {
    }

    #[Given('there are lines:')]
    public function thereAreLines(TableNode $lines): void
    {
        foreach ($lines->getHash() as $line) {
            $this->data->line($line['symbol'], $line['type'], night: ($line['night'] ?? 'no') === 'yes');
        }

        $this->data->flush();
    }

    #[When('I list lines')]
    public function iListLines(): void
    {
        $this->list([]);
    }

    #[When('I list lines :ids')]
    public function iListLinesWithIds(string $ids): void
    {
        $this->list(['id' => implode(',', self::split($ids))]);
    }

    #[When('I search for lines with symbol :symbol')]
    public function iSearchForLinesWithSymbol(string $symbol): void
    {
        $this->list(['symbol' => $symbol]);
    }

    #[When('I look up line :id')]
    public function iLookUpLine(string $id): void
    {
        $this->client->get(sprintf('/api/v1/%s/lines/%s', TestProvider::IDENTIFIER, $id));
    }

    #[Then('I should see lines :symbols')]
    public function iShouldSeeLines(string $symbols): void
    {
        $actual = array_column($this->response(), 'symbol');

        Assert::assertSame(self::split($symbols), $actual, self::describe($actual));
    }

    #[Then('I should see line :symbol of type :type running at night')]
    public function iShouldSeeNightLine(string $symbol, string $type): void
    {
        $line = $this->response();

        Assert::assertSame(
            ['symbol' => $symbol, 'type' => $type, 'night' => true],
            ['symbol' => $line['symbol'] ?? null, 'type' => $line['type'] ?? null, 'night' => $line['night'] ?? null],
            self::describe($line),
        );
    }

    private function list(array $query): void
    {
        $this->client->get(sprintf('/api/v1/%s/lines', TestProvider::IDENTIFIER), $query);
    }

    private function response(): array
    {
        Assert::assertSame(200, $this->client->lastResponse()->getStatusCode(), (string) $this->client->lastResponse()->getContent());

        return $this->client->json();
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
        return array_values(array_filter(array_map(trim(...), explode(',', $list)), fn (string $item) => $item !== ''));
    }
}
