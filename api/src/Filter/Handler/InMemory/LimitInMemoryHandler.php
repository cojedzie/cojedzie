<?php

namespace App\Filter\Handler\InMemory;

use App\Event\PostProcessEvent;
use App\Filter\Handler\PostProcessingHandler;
use App\Filter\Requirement\LimitConstraint;
use Ds\Seq;
use Kadet\Functional\Collections as c;

class LimitInMemoryHandler implements PostProcessingHandler
{
    #[\Override]
    public function postProcess(PostProcessEvent $event)
    {
        /** @var Seq $data */
        $data = $event->getData();
        /** @var LimitConstraint $modifier */
        $modifier = $event->getRequirement();

        $event->setData($data |> c\slice($modifier->getOffset(), $modifier->getCount()));
    }
}
