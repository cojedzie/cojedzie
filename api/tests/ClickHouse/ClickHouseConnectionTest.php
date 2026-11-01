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

namespace App\Tests\ClickHouse;

use App\ClickHouse\ClickHouseConnection;
use PHPUnit\Framework\TestCase;

final class ClickHouseConnectionTest extends TestCase
{
    public function testDisabledWithoutUrl(): void
    {
        $this->assertFalse(new ClickHouseConnection('')->isEnabled());
        $this->assertTrue(new ClickHouseConnection('http://clickhouse:8123/history')->isEnabled());
    }

    public function testServerIsReadFromTheUrl(): void
    {
        $client = new ClickHouseConnection('http://recorder:a+b%2Fc@clickhouse:8124/history')->client();

        $this->assertSame('clickhouse', $client->getConnectHost());
        $this->assertSame('8124', (string) $client->getConnectPort());
        $this->assertSame('recorder', $client->getConnectUsername());
        $this->assertSame('a+b/c', $client->getConnectPassword());
        $this->assertSame('history', $client->settings()->getDatabase());
    }

    public function testMalformedUrlThrowsInsteadOfFallingBack(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('COJEDZIE_CLICKHOUSE_URL');

        new ClickHouseConnection('http://user:ab/cd@clickhouse:8123/history')->client();
    }
}
