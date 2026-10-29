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
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;

/**
 * HTTP-level vocabulary, meant only for features in features/contract.
 */
final readonly class ApiContext implements Context
{
    public function __construct(
        private ApiClient $client,
    ) {
    }

    #[BeforeScenario]
    public function forgetCredentials(): void
    {
        $this->client->authenticate(null, null);
    }

    #[Given('I authenticate as :user with password :password')]
    public function iAuthenticateAs(string $user, string $password): void
    {
        $this->client->authenticate($user, $password);
    }

    #[When('I send a :method request to :path')]
    public function iSendARequestTo(string $method, string $path): void
    {
        $this->client->request($method, $path);
    }

    #[When('I send a :method request to :path with body:')]
    public function iSendARequestToWithBody(string $method, string $path, PyStringNode $body): void
    {
        $this->client->request($method, $path, body: $body->getRaw());
    }

    #[When('I send a :method request to :path with header :name: :value')]
    public function iSendARequestToWithHeader(string $method, string $path, string $name, string $value): void
    {
        $this->client->request($method, $path, headers: [$name => $value]);
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

    #[Then('the response header :name should be :value')]
    public function theResponseHeaderShouldBe(string $name, string $value): void
    {
        Assert::assertSame($value, $this->client->lastResponse()->headers->get($name));
    }

    #[Then('the response should not have the header :name')]
    public function theResponseShouldNotHaveTheHeader(string $name): void
    {
        Assert::assertFalse($this->client->lastResponse()->headers->has($name), sprintf('The response has the header "%s".', $name));
    }

    #[Then('the response\'s :field should be :value')]
    public function theResponsesFieldShouldBe(string $field, string $value): void
    {
        $actual = $this->client->json()[$field] ?? null;

        Assert::assertSame($value, is_scalar($actual) ? (string) $actual : json_encode($actual), sprintf('"%s" differs.', $field));
    }

    #[Then('the response should be a JSON list of :count items')]
    public function theResponseShouldBeAJsonListOfItems(int $count): void
    {
        $json = $this->client->json();

        Assert::assertTrue(is_array($json) && array_is_list($json), "Expected a JSON list.");
        Assert::assertCount($count, $json);
    }

    #[Then('the response should be a non-empty JSON list')]
    public function theResponseShouldBeANonEmptyJsonList(): void
    {
        $this->assertNonEmptyList($this->client->json(), 'the response');
    }

    #[Then('the response\'s :field should be a non-empty JSON list')]
    public function theResponsesFieldShouldBeANonEmptyJsonList(string $field): void
    {
        $this->assertNonEmptyList($this->client->json()[$field] ?? null, sprintf('"%s"', $field));
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

    /**
     * Compares the object at a dot-separated path (list items by index, e.g. "0.line.operator") field by field,
     * failing on any extra or missing field.
     */
    #[Then('the response\'s :path should be exactly:')]
    public function theResponsesPathShouldBeExactly(string $path, TableNode $expected): void
    {
        $actual = $this->client->json();

        foreach (explode('.', $path) as $key) {
            Assert::assertIsArray($actual, sprintf('Cannot read "%s" of "%s".', $key, $path));
            Assert::assertArrayHasKey($key, $actual, sprintf('The response has no "%s".', $path));

            $actual = $actual[$key];
        }

        Assert::assertIsArray($actual, sprintf('"%s" is not an object.', $path));

        $expected = array_column($expected->getHash(), 'value', 'field');
        $actual   = array_map(fn ($value) => is_scalar($value) ? (string) $value : json_encode($value), $actual);

        ksort($expected);
        ksort($actual);

        Assert::assertSame($expected, $actual, sprintf('"%s" differs.', $path));
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

    private function assertNonEmptyList(mixed $value, string $what): void
    {
        Assert::assertTrue(is_array($value) && array_is_list($value), sprintf('Expected %s to be a JSON list.', $what));
        Assert::assertNotEmpty($value, sprintf('Expected %s to have items.', $what));
    }
}
