<?php
/*
 * Copyright (C) 2021 Kacper Donat
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

namespace App\Service;

use App\Context\FederationContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FederatedConnectionService
{
    public const ENDPOINT_CONNECT    = '/api/v1/federation/connections';
    public const ENDPOINT_DISCONNECT = '/api/v1/federation/connections/{id}';

    public function __construct(private readonly FederationContext $context, private readonly HttpClientInterface $http)
    {
    }

    /**
     * Connect into federation network and return connection id.
     *
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     *
     * @return Uuid Connection id for this connection.
     */
    public function connect(): Uuid
    {
        $response = $this->http->request(
            'POST',
            $this->context->getHubBaseUrl().static::ENDPOINT_CONNECT,
            [
                'body' => [
                    'server_id' => $this->context->getServerId()->toRfc4122(),
                    'url'       => $this->context->getAdvertisedUrl(),
                ],
            ]
        );

        $data = $response->toArray();

        return Uuid::fromString($data['connection_id']);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function disconnect(): bool
    {
        $url = str_replace(
            '{id}',
            $this->context->getConnectionId()->toRfc4122(),
            $this->context->getHubBaseUrl().FederatedConnectionService::ENDPOINT_DISCONNECT
        );

        $response = $this->http->request(
            'DELETE',
            $url
        );

        return $response->getStatusCode() === Response::HTTP_OK;
    }
}
