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

namespace App\Filter\Handler\Database;

use App\Event\HandleDatabaseModifierEvent;
use App\Event\HandleModifierEvent;
use App\Filter\Handler\ModifierHandler;
use App\Filter\Modifier\RelatedFilterModifier;
use App\Service\EntityReferenceFactory;

class TrackByStopDatabaseHandler implements ModifierHandler
{
    public function __construct(
        private readonly EntityReferenceFactory $references
    ) {
    }

    public function process(HandleModifierEvent $event)
    {
        if (!$event instanceof HandleDatabaseModifierEvent) {
            return;
        }

        /** @var RelatedFilterModifier $modifier */
        $modifier = $event->getModifier();
        $builder  = $event->getBuilder();
        $alias    = $event->getMeta()['alias'];

        $relationship = 'stopsInTrack';

        $parameter = sprintf(":%s_%s", $alias, $relationship);
        $reference = $this->references->create($modifier->getRelated(), $event->getMeta()['provider']);

        $condition = $modifier->isMultiple() ? 'stop_in_track.stop IN (%s)' : 'stop_in_track.stop = %s';

        $builder
            ->join(sprintf("%s.%s", $alias, $relationship), 'stop_in_track')
            ->andWhere(sprintf($condition, $parameter))
            ->setParameter($parameter, $reference)
        ;
    }
}
