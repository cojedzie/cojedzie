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

namespace App\Parser\FullParser;

use App\Parser\Exception\UnexpectedTokenException;
use App\Parser\ParserInterface;
use App\Parser\StreamInterface;

class AnyParser extends AbstractParser
{
    private array $parsers;

    public function __construct(ParserInterface ...$parsers)
    {
        $this->parsers = $parsers;
    }

    public function label(): string
    {
        return implode(' or ', array_map(fn ($consumer) => $consumer->label(), $this->parsers));
    }

    public function __invoke(StreamInterface $stream)
    {
        $position = $stream->tell();

        foreach ($this->parsers as $parser) {
            try {
                if (FullParser::isValid($result = $stream->consume($parser))) {
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
