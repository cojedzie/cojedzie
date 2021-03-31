<?php

namespace App\Provider;

use App\Modifier\Modifier;
use Illuminate\Support\Collection;

interface FluentRepository extends Repository
{
    public function first(Modifier ...$modifiers);
    public function all(Modifier ...$modifiers): Collection;
}
