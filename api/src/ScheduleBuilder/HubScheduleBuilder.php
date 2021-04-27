<?php

namespace App\ScheduleBuilder;

use App\Context\FederationContext;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;

class HubScheduleBuilder implements ScheduleBuilder
{
    private FederationContext $federationContext;

    public function __construct(FederationContext $federationContext)
    {
        $this->federationContext = $federationContext;
    }

    public function buildSchedule(Schedule $schedule): void
    {
        if ($this->federationContext->isHub()) {
            $schedule
                ->addCommand('federation:check')
                ->description('Checks health of federated connections.')
                ->everyMinute()
            ;

            $schedule
                ->addCommand('federation:cleanup')
                ->description('Clean stale connections.')
                ->daily()
                ->at(0)
            ;
        }
    }
}
