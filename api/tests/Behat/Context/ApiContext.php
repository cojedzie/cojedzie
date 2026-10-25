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
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;

/**
 * HTTP-level vocabulary, meant only for features in features/contract.
 */
final class ApiContext implements Context
{
    public function __construct(
        private readonly ApiClient $client,
    ) {
    }

    #[When('I request :path')]
    public function iRequest(string $path): void
    {
        $this->client->get($path);
    }

    #[When('I request :path with query:')]
    public function iRequestWithQuery(string $path, TableNode $query): void
    {
        $parameters = [];

        foreach ($query->getHash() as ['name' => $name, 'value' => $value]) {
            if (str_ends_with($name, '[]')) {
                $parameters[substr($name, 0, -2)][] = $value;
            } else {
                $parameters[$name] = $value;
            }
        }

        $this->client->get($path, $parameters);
    }

    #[Then('the response status should be :code')]
    public function theResponseStatusShouldBe(int $code): void
    {
        $response = $this->client->lastResponse();

        Assert::assertSame($code, $response->getStatusCode(), mb_substr((string) $response->getContent(), 0, 300));
    }

    #[Then('the response should be a JSON list of :count items')]
    public function theResponseShouldBeAJsonListOfItems(int $count): void
    {
        $json = $this->client->json();

        Assert::assertTrue(is_array($json) && array_is_list($json), "Expected a JSON list.");
        Assert::assertCount($count, $json);
    }

    #[Then('every item\'s :field should have the fields:')]
    public function everyItemsFieldShouldHaveTheFields(string $field, TableNode $fields): void
    {
        foreach ($this->client->json() as $index => $item) {
            Assert::assertIsArray($item[$field] ?? null, sprintf('Item %d has no object "%s".', $index, $field));

            foreach (array_slice($fields->getColumn(0), 1) as $name) {
                Assert::assertArrayHasKey($name, $item[$field], sprintf('Item %d "%s" has no field "%s".', $index, $field, $name));
            }
        }
    }

    #[Then('every item should have the fields:')]
    public function everyItemShouldHaveTheFields(TableNode $fields): void
    {
        $json = $this->client->json();

        Assert::assertNotEmpty($json, 'Expected at least one item to check.');

        foreach ($json as $index => $item) {
            foreach ($fields->getColumn(0) as $field) {
                if ($field === 'field') {
                    continue;
                }

                Assert::assertArrayHasKey($field, $item, sprintf('Item %d has no field "%s".', $index, $field));
            }
        }
    }
}
