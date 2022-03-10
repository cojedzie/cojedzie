<?php
/*
 * Copyright (C) 2022 Kacper Donat
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

namespace App\Tests\JsonStreamingTokenizer;

use App\Parser\GeneratorStringStream;
use App\Parser\JsonStreamingTokenizer;
use PHPUnit\Framework\TestCase;

class JsonStreamingTokenizerTest extends TestCase
{
    /**
     * @dataProvider jsonFileProvider
     */
    public function testJsonFileShouldParseCorrectly(string $filename)
    {
        $stream = $this->createStreamFromJsonFile($filename);
        $parser = new JsonStreamingTokenizer();

        foreach ($parser($stream) as $token) {
            // noop
        };

        $this->assertTrue(true);
    }

    public function jsonFileProvider(): \Generator
    {
        yield 'Wikipedia example' => [__DIR__ . '/stubs/test_1.json'];
        yield 'ZTM Updates' => [__DIR__ . '/stubs/ztm_stop_updates.json'];
    }

    private function createStreamFromJsonFile(string $filename)
    {
        $generator = function () use ($filename) {
            $file = fopen($filename, 'r');

            while (!feof($file)) {
                yield fread($file, 4096);
            }

            fclose($file);
        };

        return new GeneratorStringStream($generator());
    }
}
