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
 * Takes over the http:// scheme while registered and serves recorded ZTM estimates for "…?stopId=<id>", so the
 * repository never reaches the network.
 */
final class EstimatesStream
{
    private const string SCHEME = 'http';

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
        stream_wrapper_unregister(self::SCHEME);
        stream_wrapper_register(self::SCHEME, self::class);

        self::$estimates   = [];
        self::$unavailable = false;
    }

    public static function unregister(): void
    {
        stream_wrapper_restore(self::SCHEME);
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        if (self::$unavailable) {
            return false;
        }

        parse_str((string) parse_url($path, PHP_URL_QUERY), $query);

        $this->body = json_encode(['delay' => self::$estimates[$query['stopId'] ?? ''] ?? []], JSON_THROW_ON_ERROR);

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
