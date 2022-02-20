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

use App\Entity\Federation\FederatedConnectionEntity;
use App\Exception\FederationException;
use App\Subscriber\FederationHeadersSubscriber;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class FederatedConnectionChecker
{
    /**
     * Minimum interval between consecutive checks.
     */
    final const CHECK_INTERVAL = 60;

    /**
     * Maximum failures in-line to assume that this connection is dead.
     */
    final const FAILURE_THRESHOLD = 5;

    /**
     * Health endpoint to check on the federated nodes.
     */
    final const STATUS_ENDPOINT = "/api/v1/status";

    public function __construct(private readonly EntityManagerInterface $manager, private readonly HttpClientInterface $http, private readonly HubInterface $hub, private readonly FederatedConnectionUpdateFactory $updateFactory)
    {
    }

    public function check(FederatedConnectionEntity $connection, bool $force = false)
    {
        $now = Carbon::now();

        // For closed connection this method is noop, it should not even be called but it's hard to predict and control.
        if ($connection->isClosed()) {
            return;
        }

        if (!$force && $connection->getNextCheck()->isAfter($now)) {
            return;
        }

        $connection->setLastCheck($now);

        try {
            $response = $this->http->request(
                'GET',
                $connection->getUrl().self::STATUS_ENDPOINT,
            );

            $this->validateResponse($response, $connection);

            $connection->setLastStatus($response->getContent());

            if ($connection->getState() === FederatedConnectionEntity::STATE_NEW) {
                $this->hub->publish($this->updateFactory->createNodeJoinedUpdate($connection));
            } elseif ($connection->getState() === FederatedConnectionEntity::STATE_BACKOFF) {
                $this->hub->publish($this->updateFactory->createNodeResumeUpdate($connection));
            }

            if (!$connection->isSuspended()) {
                $connection->setState(FederatedConnectionEntity::STATE_READY);
            }
        } catch (HttpClientExceptionInterface|FederationException) {
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

        if ($connection->getFailures() > self::FAILURE_THRESHOLD) {
            $connection->setState(FederatedConnectionEntity::STATE_ERROR);
            $connection->setClosedAt(Carbon::now());

            $this->hub->publish($this->updateFactory->createNodeLeftUpdate($connection));
            return;
        }

        $connection->setState(FederatedConnectionEntity::STATE_BACKOFF);

        $this->hub->publish($this->updateFactory->createNodeSuspendUpdate($connection));
    }

    private function calculateNextCheck(FederatedConnectionEntity $connection): Carbon
    {
        // k is multiplier of check interval.
        // We pick random number between consecutive powers of 2, for example, if we failed 4 times in row we pick
        // random number between 2^3 = 8 and 2^4 = 16
        $k = random_int(
            2 ** ($connection->getFailures() - 1),
            2 ** $connection->getFailures()
        );

        // k should be at least 1
        $k = max(1, $k);

        return Carbon::now()->addSeconds($k * self::CHECK_INTERVAL);
    }
}
