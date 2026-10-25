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

namespace App\Tests\Filter\Requirement;

use App\Filter\Requirement\LimitConstraint;
use PHPUnit\Framework\TestCase;

final class LimitConstraintTest extends TestCase
{
    public function testNegativeOffsetIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new LimitConstraint(offset: -1, count: 10);
    }

    public function testNegativeCountIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new LimitConstraint(offset: 0, count: -1);
    }

    public function testZeroAndUnlimitedAreValid(): void
    {
        $this->assertSame(0, new LimitConstraint(offset: 0, count: 0)->getCount());
        $this->assertNull(new LimitConstraint(offset: 5)->getCount());
    }
}
