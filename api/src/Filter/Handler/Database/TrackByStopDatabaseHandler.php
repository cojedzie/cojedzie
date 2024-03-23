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

use App\Event\HandleDatabaseRequirementEvent;
use App\Event\HandleRequirementEvent;
use App\Filter\Handler\ModifierHandler;
use App\Filter\Requirement\RelatedFilter;
use App\Service\EntityReferenceFactory;

class TrackByStopDatabaseHandler implements ModifierHandler
{
    public function __construct(
        private readonly EntityReferenceFactory $references
    ) {
    }

    #[\Override]
    public function process(HandleRequirementEvent $event)
    {
        if (!$event instanceof HandleDatabaseRequirementEvent) {
            return;
        }

        /** @var RelatedFilter $modifier */
        $modifier = $event->getRequirement();
        $builder  = $event->getBuilder();
        $alias    = $event->getMeta()['alias'];

        $relationship = $modifier->getRelationship() === 'destination' ? 'final' : 'stopsInTrack';

        $parameter = sprintf(":%s_%s", $alias, $relationship);
        $reference = $this->references->create($modifier->getRelated(), $event->getMeta()['provider']);

        $condition = $modifier->isMultiple() ? "$relationship.stop IN (%s)" : "$relationship.stop = %s";

        $builder
            ->join(sprintf("%s.%s", $alias, $relationship), $relationship)
            ->andWhere(sprintf($condition, $parameter))
            ->setParameter($parameter, $reference)
        ;
    }
}
