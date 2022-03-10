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

class RepeatedConsumer extends AbstractStreamingConsumer
{
    public function __construct(
        private StreamingConsumerInterface $consumer
    ) {
        $this->consumer = $this->consumer->optional();
    }

    public function label(): string
    {
        return "multiple " . $this->consumer->label();
    }

    public function __invoke(StreamInterface $stream)
    {
        $successful = false;

        do {
            $result = $stream->consume($this->consumer);
            yield from $result;
            $successful = $successful || StreamingConsumer::isValid($result);
        } while (StreamingConsumer::isValid($result));

        return $successful;
    }
}
