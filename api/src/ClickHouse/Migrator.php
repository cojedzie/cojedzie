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

namespace App\ClickHouse;

use Kadet\Functional\Collections as c;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Applies the ClickHouse migrations that haven't been applied yet, recording each in migration_versions.
 */
final readonly class Migrator
{
    private const string VERSIONS = <<<'SQL'
    CREATE TABLE IF NOT EXISTS migration_versions (
        version String,
        executed_at DateTime('UTC')
    ) ENGINE = ReplacingMergeTree
    ORDER BY version
    SQL;

    /**
     * @param iterable<Migration> $migrations
     */
    public function __construct(
        private ClickHouseConnection $connection,
        #[AutowireIterator('app.clickhouse_migration')]
        private iterable $migrations,
    ) {
    }

    /**
     * @return list<Migration> the migrations applied now, in order
     */
    public function migrate(): array
    {
        $client = $this->connection->client();
        $client->write(self::VERSIONS);

        $applied = $client->select('SELECT DISTINCT version FROM migration_versions')->rows()
            |> c\map(fn (array $row) => $row['version'])
            |> c\toArray();

        $pending = $this->migrations
            |> c\toSeq()
            |> c\filter(fn (Migration $migration) => !in_array(self::version($migration), $applied, true))
            |> c\sortBy(self::version(...))
            |> c\toArray();

        foreach ($pending as $migration) {
            $migration->up($client);
            $client->insert('migration_versions', [[self::version($migration), gmdate('Y-m-d H:i:s')]], ['version', 'executed_at']);
        }

        return $pending;
    }

    public static function version(Migration $migration): string
    {
        return new \ReflectionClass($migration)->getShortName();
    }
}
