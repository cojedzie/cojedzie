<?php

namespace App\Filter\Handler\InMemory;

use App\Event\PostProcessEvent;
use App\Filter\Handler\PostProcessingHandler;

class LimitInMemoryHandler implements PostProcessingHandler
{
    public function postProcess(PostProcessEvent $event)
    {
        /** @var \Illuminate\Support\Collection $data */
        $data = $event->getData();
        /** @var \App\Filter\Requirement\LimitConstraint $modifier */
        $modifier = $event->getRequirement();

        $event->setData(
            $data->slice(
                offset: $modifier->getOffset(),
                length: $modifier->getCount(),
            )
        );
    }
}
