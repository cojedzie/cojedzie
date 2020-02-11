<?php

namespace App\Handlers;

use App\Event\HandleModifierEvent;

interface ModifierHandler
{
    public function process(HandleModifierEvent $event);
}
