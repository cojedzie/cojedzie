<?php

namespace App\Handler\Database;

use App\Entity\TrackStopEntity;
use App\Event\HandleDatabaseModifierEvent;
use App\Event\HandleModifierEvent;
use App\Handler\ModifierHandler;
use App\Modifier\RelatedFilter;
use App\Service\EntityReferenceFactory;

class TrackByStopDatabaseHandler implements ModifierHandler
{
    private $references;

    public function __construct(EntityReferenceFactory $references)
    {
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

        $relationship = 'stopsInTrack';

        $parameter = sprintf(":%s_%s", $alias, $relationship);
        $reference = $this->references->create($modifier->getRelated(), $event->getMeta()['provider']);

        $builder
            ->join(sprintf("%s.%s", $alias, $relationship), 'stop_in_track')
            ->andWhere(sprintf("stop_in_track.stop = %s", $parameter))
            ->setParameter($parameter, $reference)
        ;
    }
}
