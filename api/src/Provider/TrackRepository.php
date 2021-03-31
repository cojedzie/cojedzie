<?php

namespace App\Provider;

use App\Modifier\Modifier;
use Illuminate\Support\Collection;

interface TrackRepository extends FluentRepository
{
    public function stops(Modifier ...$modifiers): Collection;
}
