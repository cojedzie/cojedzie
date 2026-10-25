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

use App\Exception\NonExistentServiceException;
use App\Provider\DepartureRepository;
use App\Provider\LineRepository;
use App\Provider\MessageRepository;
use App\Provider\StopRepository;
use App\Provider\TrackRepository;
use App\Provider\TripRepository;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Resolves provider repository arguments of controllers from the {provider} route attribute.
 */
#[AutoconfigureTag('controller.argument_value_resolver', ['priority' => 150])]
final readonly class RepositoryValueResolver implements ValueResolverInterface
{
    public function __construct(
        private ProviderResolver $resolver
    ) {
    }

    #[\Override]
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $type = $argument->getType();

        if ($type === null || !$request->attributes->has('provider')) {
            return [];
        }

        $getter = match (true) {
            is_a($type, StopRepository::class, true)      => 'getStopRepository',
            is_a($type, LineRepository::class, true)      => 'getLineRepository',
            is_a($type, DepartureRepository::class, true) => 'getDepartureRepository',
            is_a($type, MessageRepository::class, true)   => 'getMessageRepository',
            is_a($type, TrackRepository::class, true)     => 'getTrackRepository',
            is_a($type, TripRepository::class, true)      => 'getTripRepository',
            default                                       => null,
        };

        if ($getter === null) {
            return [];
        }

        $name = $request->attributes->get('provider');

        try {
            return [$this->resolver->resolve($name)->$getter()];
        } catch (NonExistentServiceException $exception) {
            throw new NotFoundHttpException("There is no such provider as '$name'.", $exception);
        }
    }
}
