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

namespace App\Tests\DataImport;

use App\Service\JsonStreamer;

/**
 * Serves feed rows from a callback, given the url and branch, instead of fetching and parsing upstream JSON.
 */
final class StubJsonStreamer extends JsonStreamer
{
    /**
     * @param \Closure(string, string): iterable<array<string, mixed>> $rows
     */
    public function __construct(
        private readonly \Closure $rows
    ) {
    }

    #[\Override]
    public function stream(string $url, string $branch): iterable
    {
        return ($this->rows)($url, $branch);
    }
}
