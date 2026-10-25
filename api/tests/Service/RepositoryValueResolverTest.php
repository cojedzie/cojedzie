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

declare(strict_types=1);

namespace App\Tests\Service;

use App\Provider\StopRepository;
use App\Service\ProviderResolver;
use App\Service\RepositoryValueResolver;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class RepositoryValueResolverTest extends KernelTestCase
{
    private ProviderResolver $providers;
    private RepositoryValueResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();

        $this->providers = self::getContainer()->get(ProviderResolver::class);
        $this->resolver  = new RepositoryValueResolver($this->providers);
    }

    public function testResolvesStopRepositoryForProviderAttribute(): void
    {
        $request = new Request(attributes: ['provider' => 'dummy']);

        $result = [...$this->resolver->resolve($request, $this->argument(StopRepository::class))];

        $this->assertCount(1, $result);
        $this->assertSame($this->providers->resolve('dummy')->getStopRepository(), $result[0]);
    }

    public function testIgnoresUnsupportedArgumentTypes(): void
    {
        $request = new Request(attributes: ['provider' => 'dummy']);

        $this->assertSame([], [...$this->resolver->resolve($request, $this->argument(\DateTimeInterface::class))]);
    }

    public function testIgnoresRequestsWithoutProvider(): void
    {
        $this->assertSame([], [...$this->resolver->resolve(new Request(), $this->argument(StopRepository::class))]);
    }

    public function testUnknownProviderIsNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);

        [...$this->resolver->resolve(new Request(attributes: ['provider' => 'nope']), $this->argument(StopRepository::class))];
    }

    public function testDoesNotReplaceProviderAttribute(): void
    {
        $request = new Request(attributes: ['provider' => 'dummy']);

        [...$this->resolver->resolve($request, $this->argument(StopRepository::class))];

        $this->assertSame('dummy', $request->attributes->get('provider'));
    }

    private function argument(string $type): ArgumentMetadata
    {
        return new ArgumentMetadata('repository', $type, false, false, null);
    }
}
