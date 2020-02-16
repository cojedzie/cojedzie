<?php

namespace App\Handler;

use App\Event\HandleModifierEvent;
use App\Event\PostProcessEvent;

interface PostProcessingHandler
{
    public function process(PostProcessEvent $event);
}
