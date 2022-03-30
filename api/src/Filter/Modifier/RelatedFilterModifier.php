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
use App\Model\Referable;
use App\Utility\IterableUtils;

class RelatedFilterModifier implements Modifier
{
    private $relationship;
    private $reference;

    public function __construct($reference, ?string $relation = null)
    {
        if (!is_iterable($reference) && !$reference instanceof Referable) {
            throw InvalidArgumentException::invalidType('object', $reference, [Referable::class, 'iterable']);
        }

        $this->reference    = is_iterable($reference) ? IterableUtils::toArray($reference) : $reference;
        $this->relationship = $relation ?: $reference::class;
    }

    public function getRelationship(): string
    {
        return $this->relationship;
    }

    public function getRelated()
    {
        return $this->reference;
    }

    public function isMultiple()
    {
        return is_array($this->reference);
    }
}
