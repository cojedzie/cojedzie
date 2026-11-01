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

use App\ClickHouse\Migration;
use App\ClickHouse\Migrator;
use App\Tests\ClickHouse\Support\TestClickHouse;
use ClickHouseDB\Client;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('clickhouse')]
final class MigratorTest extends TestCase
{
    #[\Override]
    protected function setUp(): void
    {
        LoggedMigration::$log = [];
    }

    public function testEveryMigrationIsAppliedOnceInOrder(): void
    {
        $connection = TestClickHouse::connection();
        $migrations = [new LoggedMigration20261102000000(), new LoggedMigration20261101000000()];

        $first  = new Migrator($connection, $migrations)->migrate();
        $second = new Migrator($connection, $migrations)->migrate();

        $this->assertSame(['LoggedMigration20261101000000', 'LoggedMigration20261102000000'], array_map(Migrator::version(...), $first));
        $this->assertSame([], $second);
        $this->assertSame(['LoggedMigration20261101000000', 'LoggedMigration20261102000000'], LoggedMigration::$log);
    }

    public function testOnlyNewMigrationsAreApplied(): void
    {
        $connection = TestClickHouse::connection();

        new Migrator($connection, [new LoggedMigration20261101000000()])->migrate();
        $applied = new Migrator($connection, [new LoggedMigration20261101000000(), new LoggedMigration20261102000000()])->migrate();

        $this->assertSame(['LoggedMigration20261102000000'], array_map(Migrator::version(...), $applied));
    }
}

abstract class LoggedMigration implements Migration
{
    /**
     * @var list<string> versions in the order they ran
     */
    public static array $log = [];

    #[\Override]
    public function getDescription(): string
    {
        return 'Logs that it ran';
    }

    #[\Override]
    public function up(Client $client): void
    {
        $client->select('SELECT 1');
        self::$log[] = Migrator::version($this);
    }
}

final class LoggedMigration20261101000000 extends LoggedMigration
{
}

final class LoggedMigration20261102000000 extends LoggedMigration
{
}
