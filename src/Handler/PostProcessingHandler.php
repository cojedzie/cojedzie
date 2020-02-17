<?php

namespace App\Handler;

use App\Event\PostProcessEvent;

interface PostProcessingHandler
{
    public function postProcess(PostProcessEvent $event);
}
