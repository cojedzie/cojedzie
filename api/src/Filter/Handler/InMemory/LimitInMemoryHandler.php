<?php

namespace App\Filter\Handler\InMemory;

use App\Event\PostProcessEvent;
use App\Filter\Handler\PostProcessingHandler;
use App\Filter\Requirement\LimitConstraint;
use Illuminate\Support\Collection;

class LimitInMemoryHandler implements PostProcessingHandler
{
    public function postProcess(PostProcessEvent $event)
    {
        /** @var Collection $data */
        $data = $event->getData();
        /** @var LimitConstraint $modifier */
        $modifier = $event->getRequirement();

        $event->setData(
            $data->slice(
                offset: $modifier->getOffset(),
                length: $modifier->getCount(),
            )
        );
    }
}
