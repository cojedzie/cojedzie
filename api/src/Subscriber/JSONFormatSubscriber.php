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

use App\Exception\InvalidFormException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

class JSONFormatSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SerializerInterface $serializer
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST   => "onRequest",
            KernelEvents::EXCEPTION => "onException",
        ];
    }

    public function onException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof InvalidFormException) {
            return;
        }

        $event->setResponse(
            new JsonResponse(
                $this->serializer->serialize($exception, 'json'),
                $exception->getStatusCode(),
                array_merge(
                    ['Content-Type' => 'application/problem+json'],
                    $exception->getHeaders()
                ),
                true,
            )
        );
    }

    public function onRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        if (!$request->attributes->has('_format')) {
            $request->attributes->set('_format', 'json');
        }

        if ($request->getContentType() === 'json') {
            $request->request->replace(json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR));
        }
    }
}
