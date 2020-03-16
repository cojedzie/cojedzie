<?php

namespace App\Handler;

use App\Event\HandleModifierEvent;

interface ModifierHandler
{
    public function process(HandleModifierEvent $event);
}
