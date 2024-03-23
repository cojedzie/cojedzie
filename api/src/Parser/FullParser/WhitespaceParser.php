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

use App\Parser\StreamInterface;

class WhitespaceParser extends AbstractParser
{
    public function label(): string
    {
        return 'whitespace';
    }

    public function __invoke(StreamInterface $stream)
    {
        $output = "";

        while ($input = $stream->peek(1)) {
            if (ctype_space((string) $input)) {
                // skip whitespace
                $output .= $stream->read(1);
            } else {
                break;
            }
        }

        return $output;
    }
}
