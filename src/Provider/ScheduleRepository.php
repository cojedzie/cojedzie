<?php

namespace App\Provider;

use App\Model\Stop;
use Tightenco\Collect\Support\Collection;

interface ScheduleRepository
{
    const DEFAULT_DEPARTURES_COUNT = 8;

    public function getDeparturesForStop(
        Stop $stop,
        \DateTime $from,
        int $count = ScheduleRepository::DEFAULT_DEPARTURES_COUNT
    ): Collection;
}
