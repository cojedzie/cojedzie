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
use App\Model\Line;
use App\Model\ScheduledStop;
use App\Model\Stop;
use App\Model\Track;
use App\Model\TrackStop;
use App\Model\Trip;
use App\Modifier\RelatedFilter;
use App\Service\IdUtils;
use App\Service\EntityReferenceFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class RelatedFilterDatabaseGenericHandler implements ModifierHandler, ServiceSubscriberInterface
{
    protected $mapping = [
        Track::class     => [
            Line::class => 'line',
            Stop::class => TrackByStopDatabaseHandler::class,
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

    private $em;
    private $inner;
    private $id;
    private $references;

    public function __construct(
        ContainerInterface $inner,
        EntityManagerInterface $em,
        IdUtils $idUtils,
        EntityReferenceFactory $references
    ) {
        $this->inner      = $inner;
        $this->em         = $em;
        $this->id         = $idUtils;
        $this->references = $references;
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

    /**
     * @inheritDoc
     */
    public static function getSubscribedServices()
    {
        return [
            TrackByStopDatabaseHandler::class,
        ];
    }
}
