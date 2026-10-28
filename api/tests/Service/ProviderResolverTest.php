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

use App\Exception\NonExistentServiceException;
use App\Provider\Dummy\DummyProvider;
use App\Provider\Provider;
use App\Service\ProviderResolver;
use PHPUnit\Framework\TestCase;

final class ProviderResolverTest extends TestCase
{
    public function testAllIsKeyedByIdentifierInOrder(): void
    {
        $resolver = new ProviderResolver([$this->provider('b'), $this->provider('a')], true);

        $all = iterator_to_array($resolver->all());

        $this->assertSame(['b', 'a'], array_keys($all));
        $this->assertSame('a', $all['a']->getIdentifier());
    }

    public function testDummyProviderIsHiddenOutsideDebug(): void
    {
        $providers = [$this->provider('a'), $this->provider('dummy', DummyProvider::class)];

        $this->assertSame(['a', 'dummy'], array_keys(iterator_to_array((new ProviderResolver($providers, true))->all())));
        $this->assertSame(['a'], array_keys(iterator_to_array((new ProviderResolver($providers, false))->all())));
    }

    public function testResolveFindsProviderOrReturnsNullForEmptyName(): void
    {
        $a        = $this->provider('a');
        $resolver = new ProviderResolver([$a], true);

        $this->assertSame($a, $resolver->resolve('a'));
        $this->assertNull($resolver->resolve(null));
        $this->assertNull($resolver->resolve(''));
    }

    public function testResolveListsAvailableProvidersForUnknownName(): void
    {
        $resolver = new ProviderResolver([$this->provider('a'), $this->provider('b')], true);

        $this->expectException(NonExistentServiceException::class);
        $this->expectExceptionMessage("Provider 'x' doesn't exist, you can choose from: a, b");

        $resolver->resolve('x');
    }

    public function testAllReturnsACopy(): void
    {
        $resolver = new ProviderResolver([$this->provider('a')], true);

        $all = $resolver->all();
        $all instanceof \Ds\Map ? $all->clear() : $all->forget($all->keys()->all());

        $this->assertCount(1, iterator_to_array($resolver->all()));
    }

    private function provider(string $identifier, string $class = Provider::class): Provider
    {
        $provider = $this->createStub($class);
        $provider->method('getIdentifier')->willReturn($identifier);

        return $provider;
    }
}
