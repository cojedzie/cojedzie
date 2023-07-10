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
        private float $sampleRate,
        private RequestStack $requestStack,
        private ConsoleCommandContext $consoleCommandContext,
        private RouterInterface $router,
        private CacheInterface $cache,
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
        return max(1.0, $this->sampleRate * $this->getDynamicSampleRate($context));
    }

    private function getDynamicSampleRate(SamplingContext $context)
    {
        if ($context->getTransactionContext()->getOp() === 'http.server') {
            $request   = $this->requestStack->getCurrentRequest();
            $routeName = $request->attributes->get('_route');

            $options = $this->cache->get(
                sprintf('route-options-%s', $routeName),
                fn () => $this->router->getRouteCollection()->get($routeName)->getOptions()
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
