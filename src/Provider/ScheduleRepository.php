<?php

namespace App\Provider;

use App\Model\Stop;
use Carbon\Carbon;
use Tightenco\Collect\Support\Collection;

interface ScheduleRepository
{
    const DEFAULT_DEPARTURES_COUNT = 16;

    public function getDeparturesForStop(
        Stop $stop,
        Carbon $from,
        int $count = ScheduleRepository::DEFAULT_DEPARTURES_COUNT
    ): Collection;
}
