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

use App\Filter\Binding\Http\LimitParameterBinding;
use App\Filter\Requirement\LimitConstraint;
use App\Service\RequirementsArgumentResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;

final class RequirementsArgumentResolverTest extends TestCase
{
    public function testUntypedParameterIsSkipped(): void
    {
        $controller = [new class() {
            #[LimitParameterBinding]
            public function action($untyped, array $requirements): void
            {
            }
        }, 'action'];
        $request = new Request(['limit' => '5']);

        new RequirementsArgumentResolver($this->createStub(ArgumentResolverInterface::class))->getArguments($request, $controller);

        $this->assertFalse($request->attributes->has('untyped'));
        $this->assertContainsOnlyInstancesOf(LimitConstraint::class, $request->attributes->get('requirements'));
    }
}
