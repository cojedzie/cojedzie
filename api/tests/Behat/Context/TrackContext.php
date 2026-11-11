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

use App\Dto\TrackKind;
use App\Tests\Behat\Support\ApiClient;
use App\Tests\Behat\Support\Provider\TestProvider;
use App\Tests\Behat\Support\TestData;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Kadet\Functional\Collections as c;
use PHPUnit\Framework\Assert;

final readonly class TrackContext implements Context
{
    public function __construct(
        private TestData $data,
        private ApiClient $client,
    ) {
    }

    #[Given('there are tracks:')]
    public function thereAreTracks(TableNode $tracks): void
    {
        foreach ($tracks->getHash() as $track) {
            $this->data->track(
                $track['line'],
                array_map(trim(...), explode(',', $track['stops'])),
                kind: TrackKind::from($track['kind']),
            );
        }

        $this->data->flush();
    }

    #[When('I list tracks of lines :ids')]
    public function iListTracksOfLines(string $ids): void
    {
        $this->client->get(sprintf('/api/v1/%s/tracks', TestProvider::IDENTIFIER), [
            'line' => explode(',', $ids) |> c\map(trim(...)) |> c\join(','),
        ]);
    }

    /**
     * Compares the columns the table has: the line's symbol, the kind, and the ids of the origin and destination.
     */
    #[Then('I should see tracks:')]
    public function iShouldSeeTracks(TableNode $expected): void
    {
        $response = $this->client->lastResponse();
        Assert::assertSame(200, $response->getStatusCode(), (string) $response->getContent());

        $columns = $expected->getRow(0);
        $actual  = array_map(
            fn (array $track) => array_intersect_key([
                'line'        => $track['line']['symbol'] ?? null,
                'kind'        => $track['kind'] ?? null,
                'origin'      => $track['origin']['id'] ?? null,
                'destination' => $track['destination']['id'] ?? null,
            ], array_flip($columns)),
            $this->client->json(),
        );

        Assert::assertSame(
            $expected->getHash(),
            $actual,
            'Actual: ' . json_encode($actual, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        );
    }
}
