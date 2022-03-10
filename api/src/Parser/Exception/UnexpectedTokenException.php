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

namespace App\Parser\Exception;

use App\Parser\Position;
use JetBrains\PhpStorm\Pure;

class UnexpectedTokenException extends \RuntimeException
{
    #[Pure]
    public static function createWithExpected(string $got, string $expected, Position $position)
    {
        return new static("Expected {$expected}, got {$got} at {$position->line}:{$position->column}.");
    }

    #[Pure]
    public static function create(string $message, Position $position)
    {
        return new static("{$message} at {$position->line}:{$position->column}.");
    }
}
