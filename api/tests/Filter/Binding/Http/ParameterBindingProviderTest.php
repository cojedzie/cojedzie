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

namespace App\Tests\Filter\Binding\Http;

use App\Exception\InvalidArgumentException;
use App\Filter\Binding\Http\EmbedParameterBinding;
use App\Filter\Binding\Http\LimitParameterBinding;
use App\Filter\Binding\Http\ParameterBindingProvider;
use App\Filter\Requirement\Embed;
use App\Filter\Requirement\LimitConstraint;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class ParameterBindingProviderTest extends TestCase
{
    public function testUsesBindingReturnedByCallable(): void
    {
        $provider = new ParameterBindingProvider(fn () => new EmbedParameterBinding(['stops']));

        $requirements = [...$provider->getRequirementsFromRequest(new Request(['embed' => 'stops']))];

        $this->assertCount(1, $requirements);
        $this->assertInstanceOf(Embed::class, $requirements[0]);
    }

    public function testInstantiatesBindingFromClassName(): void
    {
        $provider = new ParameterBindingProvider(LimitParameterBinding::class);

        $requirements = [...$provider->getRequirementsFromRequest(new Request())];

        $this->assertInstanceOf(LimitConstraint::class, $requirements[0]);
    }

    public function testRejectsSourceThatIsNeitherCallableNorClass(): void
    {
        $provider = new ParameterBindingProvider('app.not_a_class');

        $this->expectException(InvalidArgumentException::class);

        [...$provider->getRequirementsFromRequest(new Request())];
    }
}
