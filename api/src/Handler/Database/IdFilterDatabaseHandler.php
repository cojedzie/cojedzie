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

namespace App\Handler\Database;

use App\Handler\ModifierHandler;
use App\Modifier\IdFilter;
use App\Event\HandleDatabaseModifierEvent;
use App\Event\HandleModifierEvent;
use App\Service\IdUtils;
use function Kadet\Functional\apply;

class IdFilterDatabaseHandler implements ModifierHandler
{
    public function __construct(private readonly IdUtils $id)
    {
    }

    public function process(HandleModifierEvent $event)
    {
        if (!$event instanceof HandleDatabaseModifierEvent) {
            return;
        }

        /** @var IdFilter $modifier */
        $modifier = $event->getModifier();
        $builder  = $event->getBuilder();
        $alias    = $event->getMeta()['alias'];
        $provider = $event->getMeta()['provider'];

        $id       = $modifier->getId();
        $mapper   = apply([$this->id, 'generate'], $provider);

        $builder
            ->andWhere($modifier->isMultiple() ? "{$alias} in (:id)" : "{$alias} = :id")
            ->setParameter(':id', $modifier->isMultiple() ? array_map($mapper, $id) : $mapper($id));
        ;
    }
}
