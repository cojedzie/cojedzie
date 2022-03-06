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

namespace App\Parser\StreamingConsumer;

use App\Parser\StreamingConsumerInterface;
use App\Parser\StreamInterface;

class BetweenConsumer extends AbstractConsumer
{
    private StreamingConsumerInterface $left;
    private StreamingConsumerInterface $right;

    public function __construct(
        private StreamingConsumerInterface $value,
        StreamingConsumerInterface $left,
        StreamingConsumerInterface $right = null,
    ) {
        $this->left  = $left;
        $this->right = $right ?: $left;
    }

    public function label(): string
    {
        return sprintf(
            "%s between %s and %s",
            $this->value->label(),
            $this->left->label(),
            $this->right->label()
        );
    }

    public function __invoke(StreamInterface $stream): \Generator
    {
        $stream->skip($this->left);

        $results = $stream->consume($this->value);
        yield from $results;

        $stream->skip($this->right);

        return $results->getReturn();
    }
}
