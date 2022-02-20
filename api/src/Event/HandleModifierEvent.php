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

namespace App\Event;

use App\Modifier\Modifier;
use App\Provider\Repository;

class HandleModifierEvent
{
    public function __construct(private readonly Modifier $modifier, private readonly Repository $repository, private readonly array $meta = [])
    {
    }

    public function getModifier(): Modifier
    {
        return $this->modifier;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }
}
