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

namespace App\Tests\ZtmGdansk\Support;

/**
 * Serves recorded ZTM departures (format of the TRISTAR "departures" API) for "ztmtest://api/departures?stopId=<id>", used as the repository's API URL so it
 * never reaches the network.
 */
final class EstimatesStream
{
    public const string URL = 'ztmtest://api';

    private const string SCHEME = 'ztmtest';

    /**
     * @var array<string, list<array<string, mixed>>> estimates by stop id
     */
    public static array $estimates = [];

    /**
     * When set, every request fails like an unreachable API.
     */
    public static bool $unavailable = false;

    /**
     * @var resource|null
     */
    public $context;
    private string $body  = '';
    private int $position = 0;

    public static function register(): void
    {
        if (!in_array(self::SCHEME, stream_get_wrappers(), true)) {
            stream_wrapper_register(self::SCHEME, self::class);
        }

        self::$estimates   = [];
        self::$unavailable = false;
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        if (self::$unavailable) {
            return false;
        }

        parse_str((string) parse_url($path, PHP_URL_QUERY), $query);

        $this->body = json_encode([
            'lastUpdate' => '2026-10-20T10:00:00Z',
            'departures' => self::$estimates[$query['stopId'] ?? ''] ?? [],
        ], JSON_THROW_ON_ERROR);

        return true;
    }

    public function stream_read(int $count): string
    {
        $chunk = substr($this->body, $this->position, $count);
        $this->position += strlen($chunk);

        return $chunk;
    }

    public function stream_eof(): bool
    {
        return $this->position >= strlen($this->body);
    }

    public function stream_stat(): array
    {
        return ['size' => strlen($this->body)];
    }
}
