<?php

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

    private FederationContext $context;
    private HttpClientInterface $http;

    public function __construct(FederationContext $context, HttpClientInterface $http)
    {
        $this->context = $context;
        $this->http = $http;
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
