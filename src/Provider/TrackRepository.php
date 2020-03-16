<?php

namespace App\Provider;

use App\Model\Track;
use App\Modifier\Modifier;
use Tightenco\Collect\Support\Collection;

interface TrackRepository extends FluentRepository
{
    public function stops(Modifier ...$modifiers): Collection;
}
