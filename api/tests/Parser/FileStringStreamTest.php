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

namespace App\Tests\Parser;

use App\Parser\Exception\StreamOpenException;
use App\Parser\FileStringStream;
use PHPUnit\Framework\TestCase;

final class FileStringStreamTest extends TestCase
{
    public function testReadsFile(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'stream');
        file_put_contents($file, '{"a": 1}');

        $stream = new FileStringStream($file);

        $this->assertSame('{"a"', $stream->read(4));
    }

    public function testFailsWithSourceWhenFileCannotBeOpened(): void
    {
        $this->expectException(StreamOpenException::class);
        $this->expectExceptionMessage('/nonexistent/agencies.json');

        new FileStringStream('/nonexistent/agencies.json');
    }
}
