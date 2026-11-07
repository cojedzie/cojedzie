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

namespace App\Tests\Entity;

use App\Entity\ValidityAwareInterface;
use App\Entity\ValidityTrait;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

final class ValidityTraitTest extends TestCase
{
    public function testOpenEndedIsValidLater(): void
    {
        $subject = $this->subject('2026-11-06 00:00', null);

        $this->assertTrue($subject->isValidAt(new Carbon('2030-01-01')));
        $this->assertFalse($subject->isValidAt(new Carbon('2026-11-05 23:59')));
    }

    public function testBoundsAreInclusive(): void
    {
        $subject = $this->subject('2026-11-06 00:00', '2026-11-06 23:59:59');

        $this->assertTrue($subject->isValidAt(new Carbon('2026-11-06 00:00')));
        $this->assertTrue($subject->isValidAt(new Carbon('2026-11-06 23:59:59')));
        $this->assertFalse($subject->isValidAt(new Carbon('2026-11-07 00:00')));
    }

    public function testNoBoundsIsAlwaysValid(): void
    {
        $this->assertTrue($this->subject(null, null)->isValidAt(new Carbon('1999-01-01')));
    }

    private function subject(?string $from, ?string $to): ValidityAwareInterface
    {
        $subject = new class() implements ValidityAwareInterface {
            use ValidityTrait;
        };
        $subject->setValidFrom($from !== null ? new Carbon($from) : null);
        $subject->setValidTo($to !== null ? new Carbon($to) : null);

        return $subject;
    }
}
