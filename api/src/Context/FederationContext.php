<?php

namespace App\Context;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Uid\Uuid;

class FederationContext implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Connection ID associated with this instance.
     */
    private ?Uuid $connectionId;

    /**
     * Server ID associated with this instance.
     */
    private ?Uuid $serverId;

    /**
     * Base URL for hub managing federated servers.
     */
    private string $hubBaseUrl;

    /**
     * Base URL for this specific connection.
     */
    private ?string $advertisedUrl;

    public function __construct(?string $connectionId, ?string $serverId, string $hubBaseUrl, ?string $advertisedUrl)
    {
        $this->connectionId = $connectionId ? Uuid::fromString($connectionId) : null;
        $this->serverId = $serverId ? Uuid::fromString($serverId) : null;

        $this->hubBaseUrl = $hubBaseUrl;
        $this->advertisedUrl = $advertisedUrl;
    }

    public function isFederated(): bool
    {
        return $this->serverId !== null;
    }

    public function isConnected(): bool
    {
        return $this->isFederated() && $this->connectionId !== null;
    }

    public function getServerId(): ?Uuid
    {
        if (!$this->serverId) {
            $this->logger->notice(
                sprintf(
                    "%s::%s get called when server ID is not available. " .
                    "You first should call %s::isFederated method to check if this instance is working in federation.",
                    __CLASS__, __METHOD__, __CLASS__
                )
            );
        }

        return $this->serverId;
    }

    public function getConnectionId(): ?Uuid
    {
        if (!$this->connectionId) {
            $this->logger->notice(
                sprintf(
                    "%s::%s get called when connection ID is not available. " .
                    "You first should call %s::isConnected method to check if this instance is working in federation and is connected.",
                    __CLASS__, __METHOD__, __CLASS__
                )
            );
        }

        return $this->connectionId;
    }

    public function getHubBaseUrl(): string
    {
        return $this->hubBaseUrl;
    }

    public function getAdvertisedUrl(): ?string
    {
        return $this->advertisedUrl;
    }

    public function isHub(): bool
    {
        return !$this->isFederated();
    }
}