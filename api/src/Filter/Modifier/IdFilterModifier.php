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

namespace App\Filter\Modifier;

use App\Exception\InvalidArgumentException;
use App\Utility\IterableUtils;

class IdFilterModifier implements Modifier
{
    private readonly array|string $id;

    public function __construct(iterable|string $id)
    {
        if (!is_iterable($id) && !is_string($id)) {
            throw InvalidArgumentException::invalidType('id', $id, ['string', 'array']);
        }

        $this->id = is_iterable($id) ? IterableUtils::toArray($id) : $id;
    }

    public function getId(): array|string
    {
        return $this->id;
    }

    public function isMultiple(): bool
    {
        return is_array($this->id);
    }
}
