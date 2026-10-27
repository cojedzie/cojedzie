<?php

namespace App\Service;

use App\Context\ConsoleCommandContext;
use App\Utility\CustomSentrySampleRateInterface;
use Sentry\Tracing\SamplingContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Cache\CacheInterface;

class SentrySampler
{
    public function __construct(
        private readonly float $sampleRate,
        private readonly RequestStack $requestStack,
        private readonly ConsoleCommandContext $consoleCommandContext,
        private readonly RouterInterface $router,
        private readonly CacheInterface $cache,
    ) {
    }

    public function __invoke(SamplingContext $context): float
    {
        if ($context->getParentSampled()) {
            // If the parent transaction (for example a JavaScript front-end)
            // is sampled, also sample the current transaction
            return 1.0;
        }

        // otherwise use dynamic sample rate which should be relative to main one
        return min(1.0, $this->sampleRate * $this->getDynamicSampleRate($context));
    }

    private function getDynamicSampleRate(SamplingContext $context)
    {
        if ($context->getTransactionContext()->getOp() === 'http.server') {
            $routeName = $this->requestStack->getCurrentRequest()?->attributes->get('_route');

            // unmatched requests (404) have no route to take options from
            if ($routeName === null) {
                return 1.0;
            }

            $options = $this->cache->get(
                sprintf('route-options-%s', $routeName),
                fn () => $this->router->getRouteCollection()->get($routeName)?->getOptions() ?? []
            );

            return array_key_exists('sentry_sample_rate', $options)
                ? (float) $options['sentry_sample_rate']
                : 1.0;
        }

        if ($context->getTransactionContext()->getOp() === 'console.command') {
            $command = $this->consoleCommandContext->getCurrentCommand();

            if ($command instanceof CustomSentrySampleRateInterface) {
                return $command->getSentrySampleRate();
            }
        }

        if ($context->getTransactionContext()->getOp() === 'queue.task') {
            return (float) $context->getTransactionContext()->getMetadata()->getSamplingRate();
        }

        // just use default sampling rate
        return 1.0;
    }
}
