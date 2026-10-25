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

namespace App\Tests\Behat\Context;

use App\Tests\Behat\Support\FrozenTime;
use Behat\Behat\Context\Context;
use Behat\Hook\AfterScenario;
use Behat\Step\Given;
use Carbon\Carbon;

final class TimeContext implements Context
{
    public function __construct(
        private readonly FrozenTime $time,
    ) {
    }

    #[Given('the current time is :time in :zone')]
    public function theCurrentTimeIs(string $time, string $zone): void
    {
        $this->time->freeze($time, $zone);
    }

    #[AfterScenario]
    public function unfreezeCarbon(): void
    {
        Carbon::setTestNow();
    }
}
