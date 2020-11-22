<?php

namespace App\Controller\Api\v1;

use App\Controller\Controller;
use App\Exception\NonExistentServiceException;
use App\Service\Converter;
use App\Service\ProviderResolver;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use function Kadet\Functional\ref;

class ProviderController extends Controller
{
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
