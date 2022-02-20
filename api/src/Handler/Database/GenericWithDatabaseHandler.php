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

use App\Event\HandleDatabaseModifierEvent;
use App\Event\HandleModifierEvent;
use App\Handler\ModifierHandler;
use App\Model\ScheduledStop;
use App\Model\Track;
use App\Model\TrackStop;
use App\Model\Trip;
use App\Modifier\RelatedFilter;
use App\Service\EntityReferenceFactory;
use App\Service\IdUtils;
use Doctrine\ORM\EntityManagerInterface;
use function Kadet\Functional\Transforms\property;

class GenericWithDatabaseHandler implements ModifierHandler
{
    protected $mapping = [
        Track::class         => [
            'line'  => 'line',
            'stops' => 'stopsInTrack',
        ],
        Trip::class          => [
            'schedule' => 'stops.stop',
        ],
        TrackStop::class     => [
            'track' => 'track',
        ],
        ScheduledStop::class => [
            'trip'        => 'trip',
            'track'       => 'trip.track',
            'destination' => 'trip.track.final',
        ],
    ];

    public function __construct(private readonly EntityManagerInterface $em, private readonly IdUtils $id, private readonly EntityReferenceFactory $references)
    {
    }

    public function process(HandleModifierEvent $event)
    {
        if (!$event instanceof HandleDatabaseModifierEvent) {
            return;
        }

        /** @var RelatedFilter $modifier */
        $modifier = $event->getModifier();
        $builder  = $event->getBuilder();
        $alias    = $event->getMeta()['alias'];
        $type     = $event->getMeta()['type'];

        if (!array_key_exists($modifier->getRelationship(), $this->mapping[$type])) {
            throw new \InvalidArgumentException(
                sprintf("Relationship %s is not supported for .", $type)
            );
        }

        $relationship = $this->mapping[$type][$modifier->getRelationship()];

        foreach ($this->getRelationships($relationship, $alias) as [$relationshipPath, $relationshipAlias]) {
            $selected = collect($builder->getDQLPart('select'))->flatMap(property('parts'));

            if ($selected->contains($relationshipAlias)) {
                continue;
            }

            $builder
                ->join($relationshipPath, $relationshipAlias)
                ->addSelect($relationshipAlias);
        }
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedServices()
    {
        return [
            TrackByStopDatabaseHandler::class,
        ];
    }

    private function getRelationships($relationship, $alias)
    {
        $relationships = explode('.', $relationship);

        foreach ($relationships as $current) {
            yield [sprintf("%s.%s", $alias, $current), $alias = sprintf('%s_%s', $alias, $current)];
        }
    }
}
