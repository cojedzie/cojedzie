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

namespace App\Filter\Requirement;

use App\Dto\Referable;
use App\Utility\IterableUtils;

class RelatedFilter implements Requirement
{
    private array|Referable $reference;
    private readonly ?string $relationship;

    public function __construct(iterable|Referable $reference, ?string $relationship = null)
    {
        $this->reference    = is_iterable($reference) ? IterableUtils::toArray($reference) : $reference;
        $this->relationship = $relationship ?: $this->guessRelationship();
    }

    public function getRelationship(): string
    {
        return $this->relationship;
    }

    public function getRelated(): array|Referable
    {
        return $this->reference;
    }

    public function isMultiple(): bool
    {
        return is_array($this->reference);
    }

    private function guessRelationship(): string
    {
        return ($this->isMultiple() ? $this->reference[0] : $this->reference)::class;
    }
}
