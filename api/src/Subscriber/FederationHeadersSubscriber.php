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

namespace App\Subscriber;

use App\Context\FederationContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class FederationHeadersSubscriber implements EventSubscriberInterface
{
    final public const CONNECTION_ID_HEADER = 'X-CoJedzie-Connection-Id';
    final public const SERVER_ID_HEADER     = 'X-CoJedzie-Server-Id';

    public function __construct(
        private readonly FederationContext $federationContext
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onResponse(ResponseEvent $event)
    {
        // Headers should only be added on federated servers
        if (!$this->federationContext->isFederated()) {
            return;
        }

        $response = $event->getResponse();

        $response->headers->set(self::SERVER_ID_HEADER, $this->federationContext->getServerId()->toRfc4122());
        $response->headers->set(self::CONNECTION_ID_HEADER, $this->federationContext->getConnectionId()->toRfc4122());
    }
}
