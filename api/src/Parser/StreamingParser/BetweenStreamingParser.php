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

use App\Parser\ParserInterface;
use App\Parser\StreamingParserInterface;
use App\Parser\StreamInterface;

class BetweenStreamingParser extends AbstractStreamingParser
{
    private readonly ParserInterface $right;

    public function __construct(
        private readonly StreamingParserInterface $value,
        private readonly ParserInterface $left,
        ParserInterface $right = null,
    ) {
        $this->right = $right ?: $this->left;
    }

    #[\Override]
    public function label(): string
    {
        return sprintf(
            "%s between %s and %s",
            $this->value->label(),
            $this->left->label(),
            $this->right->label()
        );
    }

    #[\Override]
    public function __invoke(StreamInterface $stream): \Generator
    {
        $stream->skip($this->left);

        $results = $stream->consume($this->value);
        yield from $results;

        $stream->skip($this->right);

        return $results->getReturn();
    }
}
