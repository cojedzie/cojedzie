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

use App\Filter\Requirement\Requirement;
use App\Provider\Repository;
use Doctrine\ORM\QueryBuilder;

class HandleDatabaseModifierEvent extends HandleModifierEvent
{
    public function __construct(
        Requirement $modifier,
        Repository $repository,
        private QueryBuilder $builder,
        array $meta = []
    ) {
        parent::__construct($modifier, $repository, $meta);
    }

    public function getBuilder(): QueryBuilder
    {
        return $this->builder;
    }

    public function replaceBuilder(QueryBuilder $builder): void
    {
        $this->builder = $builder;
    }
}
