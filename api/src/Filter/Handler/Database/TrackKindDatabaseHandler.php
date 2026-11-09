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

use App\Dto\TrackKind;
use App\Entity\TrackEntity;
use App\Entity\TrackStopEntity;
use App\Entity\TripStopEntity;
use App\Event\HandleDatabaseRequirementEvent;
use App\Event\HandleRequirementEvent;
use App\Filter\Handler\ModifierHandler;
use App\Filter\Requirement\TrackKindConstraint;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\QueryBuilder;

/**
 * Keeps tracks of the given kinds, or the track stops and timetable entries on them. Reaches the track from
 * the entity in the `entity` meta key (a track by default) under the `alias` one.
 */
class TrackKindDatabaseHandler implements ModifierHandler
{
    #[\Override]
    public function process(HandleRequirementEvent $event)
    {
        if (!$event instanceof HandleDatabaseRequirementEvent) {
            return;
        }

        /** @var TrackKindConstraint $constraint */
        $constraint = $event->getRequirement();
        $builder    = $event->getBuilder();
        $alias      = $event->getMeta()['alias'];
        $track      = $this->joinTrack($builder, $alias, $event->getMeta()['entity'] ?? TrackEntity::class);

        $builder
            ->andWhere(sprintf('%s.kind IN (:%s_track_kinds)', $track, $alias))
            ->setParameter(
                sprintf('%s_track_kinds', $alias),
                array_map(fn (TrackKind $kind) => $kind->value, $constraint->kinds),
                ArrayParameterType::STRING,
            );
    }

    /**
     * @param class-string $entity
     *
     * @return string alias of the track
     */
    private function joinTrack(QueryBuilder $builder, string $alias, string $entity): string
    {
        if ($entity === TrackEntity::class) {
            return $alias;
        }

        // own aliases, so the filter doesn't depend on joins made for embeds or by the repository
        $track = "{$alias}_kind_track";

        match ($entity) {
            TrackStopEntity::class => $builder->join("{$alias}.track", $track),
            TripStopEntity::class  => $builder
                ->join("{$alias}.trip", "{$alias}_kind_trip")
                ->join("{$alias}_kind_trip.track", $track),
            default => throw new \LogicException(sprintf('Tracks of %s can\'t be filtered by kind.', $entity)),
        };

        return $track;
    }
}
