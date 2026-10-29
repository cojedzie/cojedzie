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

namespace App\Tests;

use App\Utility\Environment;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;

final class EnvironmentTest extends TestCase
{
    #[IgnoreDeprecations]
    public function testOldNameFillsTheNewOne(): void
    {
        $this->expectUserDeprecationMessage('Since cojedzie/api 2026.10: The STALE_DATA_RETENTION environment variable is deprecated, use COJEDZIE_STALE_DATA_RETENTION.');

        $variables = Environment::withLegacyNames(['STALE_DATA_RETENTION' => '3 days']);

        $this->assertSame('3 days', $variables['COJEDZIE_STALE_DATA_RETENTION']);
    }

    public function testNewNameWinsOverTheOldOne(): void
    {
        $variables = Environment::withLegacyNames([
            'SCHEDULE_UPDATE_DATA_SOURCES'          => '0 5 * * *',
            'COJEDZIE_SCHEDULE_UPDATE_DATA_SOURCES' => '0 3 * * *',
        ]);

        $this->assertSame('0 3 * * *', $variables['COJEDZIE_SCHEDULE_UPDATE_DATA_SOURCES']);
    }

    public function testOtherVariablesAreLeftAlone(): void
    {
        $variables = ['APP_ENV' => 'prod', 'FEDERATION_HUB' => 'https://cojedzie.pl', 'SENTRY_SAMPLE_RATE' => '0.1'];

        $this->assertSame($variables, Environment::withLegacyNames($variables));
    }
}
