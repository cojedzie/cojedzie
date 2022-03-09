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

namespace App\Parser\FullConsumer;

use App\Parser\ConsumerInterface;
use App\Parser\StreamingConsumerInterface;
use App\Parser\StreamInterface;

class SeparatedByConsumer extends AbstractConsumer
{
    public function __construct(
        private ConsumerInterface $value,
        private ConsumerInterface $separator
    ) {
        $this->separator = $this->separator->optional();
    }

    public function label(): string
    {
        return sprintf(
            "%s separated by %s",
            $this->value->label(),
            $this->separator->label()
        );
    }

    public function map(callable $transform): ConsumerInterface
    {
        return new static(
            $this->value->map($transform),
            $this->separator,
        );
    }

    public function __invoke(StreamInterface $stream)
    {
        $results = [];
        do {
            $results[] = $stream->consume($this->value);
            $separator = $stream->consume($this->separator);
        } while ($separator !== null);

        return $results;
    }
}
