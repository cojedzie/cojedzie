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

namespace App\Tests\Service;

use App\Context\FederationContext;
use App\Service\FederatedConnectionService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class FederatedConnectionServiceTest extends TestCase
{
    private const string SERVER_ID     = 'a7cd192a-3dca-4fc8-b35d-91f2d6e10632';
    private const string SECRET        = 'notarealsecretatall';
    private const string CONNECTION_ID = '6a1c3d3e-7f0a-4b8e-9d55-2f6b1c0e9a11';

    public function testConnectAuthenticatesAsTheServer(): void
    {
        $response = new MockResponse(json_encode(['connection_id' => self::CONNECTION_ID]), ['http_code' => 201]);

        $connectionId = $this->service(connectionId: null, response: $response)->connect();

        $this->assertSame('POST', $response->getRequestMethod());
        $this->assertSame('http://nginx/api/v1/federation/connections', $response->getRequestUrl());
        $this->assertContains($this->authorization(), $response->getRequestOptions()['headers']);
        $this->assertSame('url=http%3A%2F%2Ffederated%3A8080', $response->getRequestOptions()['body']);
        $this->assertSame(self::CONNECTION_ID, $connectionId->toRfc4122());
    }

    public function testDisconnectAuthenticatesAsTheServer(): void
    {
        $response = new MockResponse('{}');

        $this->service(connectionId: self::CONNECTION_ID, response: $response)->disconnect();

        $this->assertSame('DELETE', $response->getRequestMethod());
        $this->assertSame('http://nginx/api/v1/federation/connections/' . self::CONNECTION_ID, $response->getRequestUrl());
        $this->assertContains($this->authorization(), $response->getRequestOptions()['headers']);
    }

    private function service(?string $connectionId, MockResponse $response): FederatedConnectionService
    {
        return new FederatedConnectionService(
            new FederationContext(
                connectionId: $connectionId,
                serverId: self::SERVER_ID,
                hubBaseUrl: 'http://nginx',
                advertisedUrl: 'http://federated:8080',
                serverSecret: self::SECRET,
            ),
            new MockHttpClient($response),
        );
    }

    private function authorization(): string
    {
        return 'Authorization: Basic ' . base64_encode(self::SERVER_ID . ':' . self::SECRET);
    }
}
