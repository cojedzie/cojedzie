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

namespace App\Controller\Api\v1;

use App\Controller\Controller;
use App\DataConverter\Converter;
use App\Exception\NonExistentServiceException;
use App\Dto\Dto;
use App\Service\ProviderResolver;
use Kadet\Functional as f;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ProviderController
 * @package App\Controller\Api\v1
 *
 * @OA\Tag(name="Providers")
 */
#[Route(path: '/providers', name: 'provider_')]
class ProviderController extends Controller
{
    #[Route(path: '', name: 'list', methods: ['GET'], options: ['version' => '1.0'])]
    public function index(ProviderResolver $resolver, Converter $converter)
    {
        $providers = $resolver
            ->all()
            ->map(f\partial(f\ref([$converter, 'convert']), f\_, Dto::class))
            ->values()
            ->toArray()
        ;
        return $this->json($providers);
    }

    #[Route(path: '/{provider}', name: 'details', methods: ['GET'], options: ['version' => '1.0'])]
    public function one(ProviderResolver $resolver, Converter $converter, $provider)
    {
        try {
            $provider = $resolver->resolve($provider);
            return $this->json($converter->convert($provider, Dto::class));
        } catch (NonExistentServiceException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }
}
