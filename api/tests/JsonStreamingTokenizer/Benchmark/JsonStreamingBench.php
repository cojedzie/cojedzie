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

namespace App\Tests\JsonStreamingTokenizer\Benchmark;

use App\Parser\FileStringStream;
use App\Parser\JsonStreamingTokenizer;
use PhpBench\Attributes\Revs;

class JsonStreamingBench
{
    #[Revs(100)]
    public function benchSimpleObject()
    {
        $parser = new JsonStreamingTokenizer();
        $stream = new FileStringStream(__DIR__.'/../stubs/test_1.json');

        /** @noinspection PhpStatementHasEmptyBodyInspection */
        foreach ($parser->parse($stream) as $token) { }
    }

    #[Revs(5)]
    public function benchZtmLines()
    {
        $parser = new JsonStreamingTokenizer();
        $stream = new FileStringStream(__DIR__.'/../stubs/ztm_lines.json');

        /** @noinspection PhpStatementHasEmptyBodyInspection */
        foreach ($parser->parse($stream) as $token) { }
    }
}
