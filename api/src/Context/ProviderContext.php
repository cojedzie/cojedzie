<?php

namespace App\Context;

use App\Provider\Provider;
use App\Service\ProviderResolver;
use Symfony\Component\HttpFoundation\RequestStack;

class ProviderContext
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ProviderResolver $providerResolver,
    ) {
    }

    public function getProvider(): ?Provider
    {
        $request  = $this->requestStack->getCurrentRequest();
        $provider = $request->attributes->get('provider');

        return $this->providerResolver->resolve($provider);
    }
}
