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

namespace App\Tests\Filter\Binding\Http;

use App\Filter\Binding\Http\Exception\InvalidParameterException;
use App\Filter\Binding\Http\IdConstraintParameterBinding;
use App\Filter\Requirement\IdConstraint;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class IdConstraintParameterBindingTest extends TestCase
{
    public function testMissingRequiredParameterIsRejected(): void
    {
        $binding = new IdConstraintParameterBinding(parameter: 'stop', required: true);

        $this->expectException(InvalidParameterException::class);

        [...$binding->getRequirementsFromRequest(new Request())];
    }

    public function testMissingOptionalParameterGivesNoRequirement(): void
    {
        $binding = new IdConstraintParameterBinding(parameter: 'stop');

        $this->assertSame([], [...$binding->getRequirementsFromRequest(new Request())]);
    }

    public function testRequiredParameterGivesConstraint(): void
    {
        $binding = new IdConstraintParameterBinding(parameter: 'stop', required: true);

        $requirements = [...$binding->getRequirementsFromRequest(new Request(['stop' => ['a', 'b']]))];

        $this->assertCount(1, $requirements);
        $this->assertInstanceOf(IdConstraint::class, $requirements[0]);
        $this->assertSame(['a', 'b'], $requirements[0]->getId());
    }
}
