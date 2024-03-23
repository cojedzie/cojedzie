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
use App\Provider\Provider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProviderParameterConverter implements ParamConverterInterface
{
    public function __construct(
        private readonly ProviderResolver $resolver
    ) {
    }

    #[\Override]
    public function apply(Request $request, ParamConverter $configuration)
    {
        $provider = $request->get('provider');

        try {
            $request->attributes->set('provider', $this->resolver->resolve($provider));
            return true;
        } catch (NonExistentServiceException $exception) {
            throw new NotFoundHttpException("There is no such provider as '$provider'.", $exception);
        }
    }

    #[\Override]
    public function supports(ParamConverter $configuration)
    {
        return $configuration->getName() === 'provider' && is_a($configuration->getClass(), Provider::class, true);
    }
}
