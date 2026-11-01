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

use ClickHouseDB\Client;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * The ClickHouse server from COJEDZIE_CLICKHOUSE_URL (http://user:password@host:8123/database, credentials
 * URL-encoded). Empty means there is none.
 */
final class ClickHouseConnection
{
    private const int DEFAULT_PORT = 8123;

    private const int CONNECT_TIMEOUT = 5;

    private const int TIMEOUT = 10;
    private ?Client $client   = null;

    public function __construct(
        #[Autowire(env: 'COJEDZIE_CLICKHOUSE_URL')]
        private readonly string $url,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->url !== '';
    }

    /**
     * @throws \InvalidArgumentException when there is no server or its URL is malformed
     */
    public function client(): Client
    {
        return $this->client ??= $this->connect();
    }

    private function connect(): Client
    {
        $url = parse_url($this->url);
        if (!$this->isEnabled() || $url === false || !isset($url['host'])) {
            throw new \InvalidArgumentException('COJEDZIE_CLICKHOUSE_URL is not a valid URL (credentials must be URL-encoded)');
        }

        $client = new Client([
            'host'     => $url['host'],
            'port'     => $url['port'] ?? self::DEFAULT_PORT,
            'username' => rawurldecode($url['user'] ?? 'default'),
            'password' => rawurldecode($url['pass'] ?? ''),
            'https'    => ($url['scheme'] ?? 'http') === 'https',
        ]);

        $client->database(trim($url['path'] ?? '', '/') ?: 'default');
        $client->setConnectTimeOut(self::CONNECT_TIMEOUT);
        $client->setTimeout(self::TIMEOUT);

        return $client;
    }
}
