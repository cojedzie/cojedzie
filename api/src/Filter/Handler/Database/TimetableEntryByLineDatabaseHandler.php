<?php
/*
 * Copyright (C) 2026 Kacper Donat
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

declare(strict_types=1);

namespace App\Filter\Handler\Database;

use App\Event\HandleDatabaseRequirementEvent;
use App\Event\HandleRequirementEvent;
use App\Filter\Handler\ModifierHandler;
use App\Filter\Requirement\RelatedFilter;
use App\Service\EntityReferenceFactory;

/**
 * Timetable entries of a line: the line of an entry is the one of its trip's track.
 */
class TimetableEntryByLineDatabaseHandler implements ModifierHandler
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

        // own aliases, so the filter doesn't depend on joins made for embeds or by the repository
        $trip  = "{$alias}_filter_trip";
        $track = "{$alias}_filter_track";
        $line  = "{$alias}_filter_line";

        $parameter = ":{$alias}_filter_line_id";
        $reference = $this->references->create($modifier->getRelated(), $event->getMeta()['provider']);

        $builder
            ->join("{$alias}.trip", $trip)
            ->join("{$trip}.track", $track)
            ->join("{$track}.line", $line)
            ->andWhere($modifier->isMultiple() ? "$line IN ($parameter)" : "$line = $parameter")
            ->setParameter($parameter, $reference)
        ;
    }
}
