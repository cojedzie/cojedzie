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
use App\Parser\StreamInterface;

class RepeatedConsumer extends AbstractConsumer
{
    public function __construct(
        private ConsumerInterface $consumer
    ) {
        $this->consumer = $this->consumer->optional();
    }

    public function label(): string
    {
        return "multiple " . $this->consumer->label();
    }

    public function __invoke(StreamInterface $stream)
    {
        $results = [];

        while (true) {
            if (!FullConsumer::isValid($result = $stream->consume($this->consumer))) {
                return $results;
            }

            $results[] = $result;
        }
    }
}
