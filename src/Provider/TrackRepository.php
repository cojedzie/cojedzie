<?php

namespace App\Provider;

use App\Model\Track;
use Tightenco\Collect\Support\Collection;

interface TrackRepository extends FluentRepository
{
    public function getByStop($stop): Collection;
}
