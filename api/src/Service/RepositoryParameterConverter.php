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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use function Kadet\Functional\any;
use function Kadet\Functional\curry;
use const Kadet\Functional\_;

class RepositoryParameterConverter implements ParamConverterInterface
{
    public function __construct(
        private readonly ProviderResolver $resolver
    ) {
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        if (!$request->attributes->has('provider')) {
            return false;
        }

        $provider = $request->attributes->get('provider');

        try {
            $provider = $this->resolver->resolve($provider);
            $class    = $configuration->getClass();
            switch (true) {
                case is_a($class, StopRepository::class, true):
                    $request->attributes->set($configuration->getName(), $provider->getStopRepository());
                    break;

                case is_a($class, LineRepository::class, true):
                    $request->attributes->set($configuration->getName(), $provider->getLineRepository());
                    break;

                case is_a($class, DepartureRepository::class, true):
                    $request->attributes->set($configuration->getName(), $provider->getDepartureRepository());
                    break;

                case is_a($class, MessageRepository::class, true):
                    $request->attributes->set($configuration->getName(), $provider->getMessageRepository());
                    break;

                case is_a($class, TrackRepository::class, true):
                    $request->attributes->set($configuration->getName(), $provider->getTrackRepository());
                    break;

                case is_a($class, TripRepository::class, true):
                    $request->attributes->set($configuration->getName(), $provider->getTripRepository());
                    break;

                default:
                    return false;
            }

            return true;
        } catch (NonExistentServiceException $exception) {
            throw new NotFoundHttpException("There is no such provider as '$provider'.", $exception);
        }
    }

    public function supports(ParamConverter $configuration)
    {
        $supports = any(array_map(curry('is_a', 3)(_, _, true), [
            StopRepository::class,
            LineRepository::class,
            DepartureRepository::class,
            MessageRepository::class,
            TrackRepository::class,
            TripRepository::class,
        ]));

        return $supports($configuration->getClass());
    }
}
