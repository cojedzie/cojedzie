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

use App\Exception\InvalidArgumentException;
use App\Modifier\Modifier;
use App\Service\IterableUtils;

class IdFilter implements Modifier
{
    /** @var string|array */
    private $id;

    public function __construct($id)
    {
        if (!is_iterable($id) && !is_string($id)) {
            throw InvalidArgumentException::invalidType('id', $id, ['string', 'array']);
        }

        $this->id = is_iterable($id) ? IterableUtils::toArray($id) : $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function isMultiple()
    {
        return is_array($this->id);
    }
}
