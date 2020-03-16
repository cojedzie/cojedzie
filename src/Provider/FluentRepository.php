<?php

namespace App\Provider;

use App\Modifier\Modifier;
use Tightenco\Collect\Support\Collection;

interface FluentRepository extends Repository
{
    public function first(Modifier ...$modifiers);
    public function all(Modifier ...$modifiers): Collection;
}
