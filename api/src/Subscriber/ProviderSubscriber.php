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

namespace App\Subscriber;

use App\Exception\NonExistentServiceException;
use App\Service\ProviderResolver;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Rejects requests for an unknown {provider} before controller arguments (and their query parameters) are
 * resolved, so a wrong path is reported as 404 even when the query is malformed as well.
 */
final readonly class ProviderSubscriber
{
    public function __construct(
        private ProviderResolver $providers,
    ) {
    }

    #[AsEventListener(event: KernelEvents::CONTROLLER)]
    public function onController(ControllerEvent $event): void
    {
        $provider = $event->getRequest()->attributes->get('provider');

        if (!is_string($provider)) {
            return;
        }

        try {
            $this->providers->resolve($provider);
        } catch (NonExistentServiceException $exception) {
            throw new NotFoundHttpException("There is no such provider as '$provider'.", $exception);
        }
    }
}
