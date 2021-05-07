<?php

namespace App\Controller\Api\v1;

use App\Controller\Controller;
use App\DataConverter\Converter;
use App\Exception\NonExistentServiceException;
use App\Model\DTO;
use App\Service\ProviderResolver;
use Kadet\Functional as f;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ProviderController
 * @package App\Controller\Api\v1
 *
 * @Route("/providers", name="provider_")
 *
 * @OA\Tag(name="Providers")
 */
class ProviderController extends Controller
{
    /**
     * @Route("", name="list", methods={"GET"}, options={"version"="1.0"})
     */
    public function index(ProviderResolver $resolver, Converter $converter)
    {
        $providers = $resolver
            ->all()
            ->map(f\partial(f\ref([$converter, 'convert']), f\_, DTO::class))
            ->values()
            ->toArray()
        ;
        return $this->json($providers);
    }

    /**
     * @Route("/{provider}", name="details", methods={"GET"}, options={"version"="1.0"})
     */
    public function one(ProviderResolver $resolver, Converter $converter, $provider)
    {
        try {
            $provider = $resolver->resolve($provider);
            return $this->json($converter->convert($provider, DTO::class));
        } catch (NonExistentServiceException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }
}
