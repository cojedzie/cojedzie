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

namespace App\Parser\Consumer;

use App\Parser\StreamInterface;

class ReducedConsumer extends AbstractConsumer
{
    public function __construct(
        private ConsumerInterface $decorated,
        private $transform,
    ) {
    }

    public function label(): string
    {
        return $this->decorated->label();
    }

    public function __invoke(StreamInterface $stream): \Generator
    {
        $results = ($this->decorated)($stream);
        $results = ($this->transform)($results);

        yield from $results;

        return $results->getReturn();
    }

    public static function join($separator = '')
    {
        return static function (\Generator $generator) use ($separator) {
            yield implode($separator, iterator_to_array($generator));

            return $generator->getReturn();
        };
    }
}
