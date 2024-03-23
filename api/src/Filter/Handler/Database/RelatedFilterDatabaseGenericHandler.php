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

use App\Dto\Line;
use App\Dto\ScheduledStop;
use App\Dto\Stop;
use App\Dto\Track;
use App\Dto\TrackStop;
use App\Dto\Trip;
use App\Event\HandleDatabaseRequirementEvent;
use App\Event\HandleRequirementEvent;
use App\Filter\Handler\ModifierHandler;
use App\Filter\Requirement\RelatedFilter;
use App\Service\EntityReferenceFactory;
use App\Service\IdUtils;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class RelatedFilterDatabaseGenericHandler implements ModifierHandler, ServiceSubscriberInterface
{
    protected $mapping = [
        Track::class => [
            Line::class   => 'line',
            'stop'        => TrackByStopDatabaseHandler::class,
            'destination' => TrackByStopDatabaseHandler::class,
        ],
        TrackStop::class => [
            Stop::class  => 'stop',
            Track::class => 'track',
        ],
        ScheduledStop::class => [
            Stop::class => 'stop',
            Trip::class => 'trip',
        ],
    ];

    public function __construct(
        private readonly ContainerInterface $inner,
        private readonly EntityManagerInterface $em,
        private readonly IdUtils $id,
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
        $type     = $event->getMeta()['type'];

        if (!array_key_exists($type, $this->mapping)) {
            throw new \InvalidArgumentException(
                sprintf("Relationship filtering for %s is not supported.", $type)
            );
        }

        if (!array_key_exists($modifier->getRelationship(), $this->mapping[$type])) {
            throw new \InvalidArgumentException(
                sprintf("Relationship %s is not supported for %s.", $modifier->getRelationship(), $type)
            );
        }

        $relationship = $this->mapping[$type][$modifier->getRelationship()];

        if ($this->inner->has($relationship)) {
            /** @var ModifierHandler $inner */
            $inner = $this->inner->get($relationship);
            $inner->process($event);

            return;
        }

        $parameter = sprintf(":%s_%s", $alias, $relationship);
        $reference = $this->references->create($modifier->getRelated(), $event->getMeta()['provider']);

        $builder
            ->join(sprintf('%s.%s', $alias, $relationship), $relationship)
            ->andWhere(sprintf($modifier->isMultiple() ? "%s in (%s)" : "%s = %s", $relationship, $parameter))
            ->setParameter($parameter, $reference);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            TrackByStopDatabaseHandler::class,
        ];
    }
}
