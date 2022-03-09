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
use App\Parser\Exception\UnexpectedTokenException;
use App\Parser\StreamInterface;

class AnyConsumer extends AbstractConsumer
{
    private array $consumers;

    public function __construct(ConsumerInterface ...$consumers)
    {
        $this->consumers = $consumers;
    }

    public function label(): string
    {
        return implode(' or ', array_map(fn ($consumer) => $consumer->label(), $this->consumers));
    }

    public function __invoke(StreamInterface $stream)
    {
        $position = $stream->tell();

        foreach ($this->consumers as $consumer) {
            try {
                if (FullConsumer::isValid($result = $stream->consume($consumer))) {
                    return $result;
                }
            } catch (UnexpectedTokenException $exception) {
                if ($position === $stream->tell()) {
                    continue;
                }

                throw $exception;
            }
        }

        return null;
    }
}
