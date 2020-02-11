<?php

namespace App\Provider;

use App\Modifiers\Modifier;

interface FluentRepository extends Repository
{
    public function first(Modifier ...$modifiers);
    public function all(Modifier ...$modifiers);
}
