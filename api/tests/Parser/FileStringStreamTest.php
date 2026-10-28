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
use App\Parser\Exception\StreamReadException;
use App\Parser\FileStringStream;
use PHPUnit\Framework\TestCase;

final class FileStringStreamTest extends TestCase
{
    /**
     * @var resource|null
     */
    private $server = null;

    #[\Override]
    protected function tearDown(): void
    {
        if ($this->server !== null) {
            proc_terminate($this->server);
            proc_close($this->server);
        }
    }

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

    public function testUnresponsiveSourceFailsToOpen(): void
    {
        $url   = $this->serve(null);
        $start = microtime(true);

        try {
            new FileStringStream($url, 1.0);
            $this->fail('Opening an unresponsive source should fail.');
        } catch (StreamOpenException) {
        }

        $this->assertLessThan(3, microtime(true) - $start);
    }

    public function testStalledReadFails(): void
    {
        $stream = new FileStringStream($this->serve('[{"a":'), 1.0);
        $start  = microtime(true);

        try {
            while (true) {
                $stream->read();
            }
        } catch (StreamReadException) {
        }

        $this->assertLessThan(3, microtime(true) - $start);
    }

    /**
     * Starts an HTTP server in a separate process that accepts one connection and then stalls without closing it:
     * before answering when $body is null, otherwise after sending the headers and $body.
     *
     * @return string the server's URL
     */
    private function serve(?string $body): string
    {
        $script = <<<'PHP'
            $server = stream_socket_server('tcp://127.0.0.1:0');
            fwrite(STDOUT, stream_socket_get_name($server, false) . "\n");
            $client = stream_socket_accept($server, 10);
            $body   = $argv[1] ?? null;
            if ($body !== null) {
                fread($client, 8192);
                fwrite($client, "HTTP/1.0 200 OK\r\nContent-Type: application/json\r\n\r\n" . $body);
            }
            sleep(30);
            PHP;

        $this->server = proc_open(
            [PHP_BINARY, '-r', $script, ...($body === null ? [] : ['--', $body])],
            [1 => ['pipe', 'w']],
            $pipes,
        );

        return sprintf('http://%s/', trim(fgets($pipes[1])));
    }
}
