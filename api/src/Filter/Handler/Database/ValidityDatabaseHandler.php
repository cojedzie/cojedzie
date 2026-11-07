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

namespace App\Filter\Handler\Database;

use App\Event\HandleDatabaseRequirementEvent;
use App\Event\HandleRequirementEvent;
use App\Filter\Handler\ModifierHandler;
use App\Filter\Requirement\ValidityConstraint;

class ValidityDatabaseHandler implements ModifierHandler
{
    #[\Override]
    public function process(HandleRequirementEvent $event)
    {
        if (!$event instanceof HandleDatabaseRequirementEvent) {
            return;
        }

        /** @var ValidityConstraint $constraint */
        $constraint = $event->getRequirement();
        $alias      = $event->getMeta()['alias'];

        // both bounds are inclusive, a missing one is open, see ValidityAwareInterface
        $event->getBuilder()
            ->andWhere(sprintf('(%1$s.validFrom IS NULL OR %1$s.validFrom <= :%1$s_valid_at)', $alias))
            ->andWhere(sprintf('(%1$s.validTo IS NULL OR %1$s.validTo >= :%1$s_valid_at)', $alias))
            ->setParameter(sprintf('%s_valid_at', $alias), $constraint->getAt(), 'datetime');
    }
}
