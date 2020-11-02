<?php

namespace App\Controller\Api\v1;

use App\Controller\Controller;
use App\Service\Converter;
use App\Service\ProviderResolver;
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
        $provider = $resolver->resolve($id);

        return $this->json($converter->convert($provider));
    }
}
