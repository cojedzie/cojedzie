<?php
/*
 * Copyright (C) 2026 Kacper Donat
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

declare(strict_types=1);

namespace App\Tests\Service;

use App\Context\ConsoleCommandContext;
use App\Service\SentrySampler;
use PHPUnit\Framework\TestCase;
use Sentry\Tracing\SamplingContext;
use Sentry\Tracing\TransactionContext;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

final class SentrySamplerTest extends TestCase
{
    private RequestStack $requestStack;
    private SentrySampler $sampler;

    #[\Override]
    protected function setUp(): void
    {
        $routes = new RouteCollection();
        $routes->add('plain', new Route('/plain'));
        $routes->add('frequent', new Route('/frequent', options: ['sentry_sample_rate' => 0.1]));

        $router = $this->createStub(RouterInterface::class);
        $router->method('getRouteCollection')->willReturn($routes);

        $this->requestStack = new RequestStack();
        $this->sampler      = new SentrySampler(0.05, $this->requestStack, new ConsoleCommandContext(), $router, new ArrayAdapter());
    }

    public function testUsesConfiguredRateForRequests(): void
    {
        $this->assertEqualsWithDelta(0.05, $this->sampleRequest('plain'), 1e-9);
    }

    public function testScalesConfiguredRateByRouteOption(): void
    {
        $this->assertEqualsWithDelta(0.005, $this->sampleRequest('frequent'), 1e-9);
    }

    public function testUsesConfiguredRateForUnmatchedRequests(): void
    {
        $this->assertEqualsWithDelta(0.05, $this->sampleRequest(null), 1e-9);
    }

    public function testSamplesWhenParentIsSampled(): void
    {
        $context = SamplingContext::getDefault(new TransactionContext());
        $context->setParentSampled(true);

        $this->assertSame(1.0, ($this->sampler)($context));
    }

    public function testScalesConfiguredRateByQueueTaskRate(): void
    {
        $transaction = new TransactionContext();
        $transaction->setOp('queue.task');
        $transaction->getMetadata()->setSamplingRate(0.5);

        $this->assertEqualsWithDelta(0.025, ($this->sampler)(SamplingContext::getDefault($transaction)), 1e-9);
    }

    private function sampleRequest(?string $route): float
    {
        $this->requestStack->push(new Request(attributes: ['_route' => $route]));

        $transaction = new TransactionContext();
        $transaction->setOp('http.server');

        return ($this->sampler)(SamplingContext::getDefault($transaction));
    }
}
