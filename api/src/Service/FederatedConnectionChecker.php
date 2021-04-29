<?php

namespace App\Service;

use App\Entity\Federation\FederatedConnectionEntity;
use App\Exception\FederationException;
use App\Subscriber\FederationHeadersSubscriber;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class FederatedConnectionChecker
{
    /**
     * Minimum interval between consecutive checks.
     */
    const CHECK_INTERVAL = 60;

    /**
     * Maximum failures in-line to assume that this connection is dead.
     */
    const FAILURE_THRESHOLD = 5;

    /**
     * Health endpoint to check on the federated nodes.
     */
    const STATUS_ENDPOINT = "/api/v1/status";

    private EntityManagerInterface $manager;
    private HttpClientInterface $http;

    public function __construct(EntityManagerInterface $manager, HttpClientInterface $http)
    {
        $this->manager = $manager;
        $this->http = $http;
    }

    public function check(FederatedConnectionEntity $connection)
    {
        $now = Carbon::now();

        // For closed connection this method is noop, it should not even be called but it's hard to predict and control.
        if ($connection->isClosed()) {
            return;
        }

        if ($connection->getNextCheck()->isAfter($now)) {
            return;
        }

        $connection->setLastCheck($now);

        try {
            $response = $this->http->request(
                'GET',
                $connection->getUrl().self::STATUS_ENDPOINT,
            );

            $this->validateResponse($response, $connection);

            if (!$connection->isSuspended()) {
                $connection->setState(FederatedConnectionEntity::STATE_READY);
            }

            $connection->setLastStatus($response->getContent());
        } catch (HttpClientExceptionInterface|FederationException $exception) {
            $this->handleFailure($connection);
        } finally {
            $connection->setNextCheck($this->calculateNextCheck($connection));

            $this->manager->persist($connection);
            $this->manager->flush();
        }
    }

    private function validateResponse(ResponseInterface $response, FederatedConnectionEntity $connection)
    {
        $headers = $response->getHeaders();

        if (array_key_exists(
            $serverIdHeader = strtolower(FederationHeadersSubscriber::SERVER_ID_HEADER),
            $headers
        )) {
            $serverId = Uuid::fromString($headers[$serverIdHeader][0]);

            if (!$serverId->equals($connection->getServer()->getId())) {
                throw new FederationException(sprintf(
                    "Expected server id %s, got %s.",
                    $connection->getServer()->getId()->toRfc4122(),
                    $serverId->toRfc4122(),
                ));
            }
        }
    }

    private function handleFailure(FederatedConnectionEntity $connection)
    {
        $connection->increaseFailureCount();
        $connection->setState(FederatedConnectionEntity::STATE_BACKOFF);

        if ($connection->getFailures() > self::FAILURE_THRESHOLD) {
            $connection->setState(FederatedConnectionEntity::STATE_ERROR);
            $connection->setClosedAt(Carbon::now());
        }
    }

    private function calculateNextCheck(FederatedConnectionEntity $connection): Carbon
    {
        // k is multiplier of check interval.
        // We pick random number between consecutive powers of 2, for example, if we failed 4 times in row we pick
        // random number between 2^3 = 8 and 2^4 = 16
        $k = rand(
            2 ** ($connection->getFailures() - 1),
            2 ** $connection->getFailures()
        );

        // k should be at least 1
        $k = max(1, $k);

        return Carbon::now()->addSeconds($k * self::CHECK_INTERVAL);
    }
}
