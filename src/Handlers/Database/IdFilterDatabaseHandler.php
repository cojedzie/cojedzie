<?php

namespace App\Handlers\Database;

use App\Handlers\ModifierHandler;
use App\Modifiers\IdFilter;
use App\Event\HandleDatabaseModifierEvent;
use App\Event\HandleModifierEvent;
use App\Service\IdUtils;
use function Kadet\Functional\apply;

class IdFilterDatabaseHandler implements ModifierHandler
{
    /**
     * @var IdUtils
     */
    private $id;

    public function __construct(IdUtils $id)
    {
        $this->id = $id;
    }

    public function process(HandleModifierEvent $event)
    {
        if (!$event instanceof HandleDatabaseModifierEvent) {
            return;
        }

        /** @var IdFilter $modifier */
        $modifier = $event->getModifier();
        $builder  = $event->getBuilder();
        $alias    = $event->getMeta()['alias'];
        $provider = $event->getMeta()['provider'];

        $id       = $modifier->getId();
        $mapper   = apply([$this->id, 'generate'], $provider);

        $builder
            ->where($modifier->isMultiple() ? "{$alias} in (:id)" : "{$alias} = :id")
            ->setParameter(':id', $modifier->isMultiple() ? array_map($mapper, $id) : $mapper($id));
        ;
    }
}
