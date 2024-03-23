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

use App\Parser\Exception\UnexpectedTokenException;
use App\Parser\StreamInterface;

class PredicateStreamingParser extends AbstractStreamingParser
{
    public function __construct(
        private $predicate,
        private readonly int $length,
        private readonly string $label
    ) {
    }

    #[\Override]
    public function label(): string
    {
        return $this->label;
    }

    #[\Override]
    public function __invoke(StreamInterface $stream): \Generator
    {
        $input = $stream->peek($this->length);

        if (!($this->predicate)($input)) {
            throw UnexpectedTokenException::createWithExpected($input, $this->label, $stream->tell());
        }

        yield $stream->read($this->length);

        return true;
    }
}
