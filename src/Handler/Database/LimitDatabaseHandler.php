<?php

namespace App\Handler\Database;

use App\Event\HandleDatabaseModifierEvent;
use App\Event\HandleModifierEvent;
use App\Handler\ModifierHandler;
use App\Modifier\Limit;

class LimitDatabaseHandler implements ModifierHandler
{
    public function process(HandleModifierEvent $event)
    {
        if (!$event instanceof HandleDatabaseModifierEvent) {
            return;
        }

        /** @var Limit $modifier */
        $modifier = $event->getModifier();
        $builder  = $event->getBuilder();

        $builder
            ->setFirstResult($modifier->getOffset())
            ->setMaxResults($modifier->getCount())
        ;
    }
}
