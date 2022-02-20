<?php
/*
 * Copyright (C) 2021 Kacper Donat
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

namespace App\Modifier;

class FieldFilter implements Modifier
{
    public function __construct(
        private readonly string $field,
        private $value,
        private readonly string $operator = '=',
        private readonly bool $caseSensitive = true
    ) {
    }

    public static function contains(string $field, string $value, bool $caseSensitive = false)
    {
        return new static($field, "%$value%", 'LIKE', $caseSensitive);
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function isCaseSensitive(): bool
    {
        return $this->caseSensitive;
    }
}
