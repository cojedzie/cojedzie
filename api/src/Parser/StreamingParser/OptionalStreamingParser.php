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

namespace App\Parser\StreamingParser;

use App\Parser\Exception\EndOfStreamException;
use App\Parser\Exception\UnexpectedTokenException;
use App\Parser\StreamingParserInterface;
use App\Parser\StreamInterface;

class OptionalStreamingParser extends AbstractStreamingParser
{
    public function __construct(
        private StreamingParserInterface $parser,
    ) {
    }

    public function label(): string
    {
        return 'optional ' . $this->parser->label();
    }

    public function __invoke(StreamInterface $stream): \Generator
    {
        $position = $stream->tell();

        try {
            $result = $this->parser->__invoke($stream);
            yield from $result;

            return $result->getReturn();
        } catch (UnexpectedTokenException|EndOfStreamException $exception) {
            if ($stream->tell() === $position) {
                return false;
            }

            // if stream was advanced we cannot backtrack so rethrow exception
            throw $exception;
        }
    }
}
