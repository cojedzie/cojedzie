<?php

namespace App\Controller\Api\v1;

use App\Controller\Controller;
use App\Exception\NonExistentServiceException;
use App\Service\Converter;
use App\Service\ProviderResolver;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use function Kadet\Functional\ref;

/**
 * Class ProviderController
 * @package App\Controller\Api\v1
 *
 * @Route("/providers")
 *
 * @OA\Tag(name="Providers")
 */
class ProviderController extends Controller
{
    /**
     * @Route("", methods={"GET"}, options={"version"="1.0"})
     */
    public function index(ProviderResolver $resolver, Converter $converter)
    {
        $providers = $resolver
            ->all()
            ->map(ref([$converter, 'convert']))
            ->values()
            ->toArray()
        ;
        return $this->json($providers);
    }

    /**
     * @Route("/{id}", methods={"GET"}, options={"version"="1.0"})
     */
    public function one(ProviderResolver $resolver, Converter $converter, $id)
    {
        try {
            $provider = $resolver->resolve($id);
            return $this->json($converter->convert($provider));
        } catch (NonExistentServiceException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }
}
