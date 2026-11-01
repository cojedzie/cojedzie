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

namespace App\Tests\ClickHouse\Support;

use App\ClickHouse\ClickHouseConnection;
use App\ClickHouse\Migrator;
use ClickHouseMigrations\Version20261101000000;

/**
 * An empty database on a real ClickHouse server, for tests in the clickhouse group. The server is
 * COJEDZIE_CLICKHOUSE_TEST_URL, by default the compose service.
 */
final class TestClickHouse
{
    private const string DEFAULT_URL = 'http://cojedzie:cojedzie@clickhouse:8123/cojedzie_test';

    public static function connection(): ClickHouseConnection
    {
        $url      = getenv('COJEDZIE_CLICKHOUSE_TEST_URL') ?: self::DEFAULT_URL;
        $database = trim((string) parse_url($url, PHP_URL_PATH), '/');

        // the server refuses requests in a database that doesn't exist, so it's created from the default one
        $server = new ClickHouseConnection(preg_replace('~/[^/]*$~', '/default', $url));
        $server->client()->write(sprintf('DROP DATABASE IF EXISTS %s', $database));
        $server->client()->write(sprintf('CREATE DATABASE %s', $database));

        return new ClickHouseConnection($url);
    }

    public static function migrator(ClickHouseConnection $connection): Migrator
    {
        return new Migrator($connection, [new Version20261101000000()]);
    }
}
