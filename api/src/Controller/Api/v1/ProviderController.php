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
use App\Dto\{CollectionResult, Dto, Provider};
use App\Exception\NonExistentServiceException;
use App\Service\ProviderResolver;
use Kadet\Functional as f;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @OA\Tag(name="Providers")
 */
#[Route(path: '/providers', name: 'provider_')]
class ProviderController extends Controller
{
    /**
     * List available data providers.
     *
     * @OA\Response(
     *     description="List of available data providers.",
     *     response=200,
     *     @OA\MediaType(
     *          mediaType="application/vnd.cojedzie.collection+json",
     *          @OA\Schema(ref=@Model(type=CollectionResult::class))
     *     ),
     *     @OA\JsonContent(type="array", @OA\Items(ref=@Model(type=Provider::class)))
     * )
     */
    #[Route(path: '', name: 'list', methods: ['GET'], options: ['version' => '1.0'])]
    public function index(ProviderResolver $resolver, Converter $converter): Response
    {
        $providers = $resolver
            ->all()
            ->map(f\partial(f\ref([$converter, 'convert']), f\_, Dto::class))
            ->values()
            ->toArray()
        ;

        return $this->apiResponseFactory->createCollectionResponse($providers);
    }

    /**
     * Get information about specific data provider
     *
     * @OA\Response(
     *     description="Data provider details.",
     *     response=200,
     *     @OA\MediaType(
     *          mediaType="application/vnd.cojedzie.provider+json",
     *          @OA\Schema(ref=@Model(type=Provider::class))
     *     ),
     * )
     */
    #[Route(path: '/{provider}', name: 'details', methods: ['GET'], options: ['version' => '1.0'])]
    public function one(ProviderResolver $resolver, Converter $converter, $provider)
    {
        try {
            $provider = $resolver->resolve($provider);

            return $this->apiResponseFactory->createResponse(
                $converter->convert($provider, Dto::class)
            );
        } catch (NonExistentServiceException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }
}
