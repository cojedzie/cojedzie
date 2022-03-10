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

namespace App\Parser;

use App\Parser\FullParser\FullParser;
use App\Parser\StreamingParser\StreamingParser;

final class ParserResult
{
    private function __construct()
    {
    }

    public static function isValid($result): bool
    {
        return $result instanceof \Generator
            ? StreamingParser::isValid($result)
            : FullParser::isValid($result);
    }

    public static function result($result)
    {
        return $result instanceof \Generator
            ? StreamingParser::result($result)
            : FullParser::result($result);
    }

    public static function isEmpty($result): bool
    {
        return $result instanceof \Generator
            ? StreamingParser::isEmpty($result)
            : FullParser::isEmpty($result);
    }
}
