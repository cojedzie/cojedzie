<?php

namespace App\Schedule;

use App\Message\UpdateDataSources;
use Symfony\Component\Messenger\Message\RedispatchMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule]
class DefaultScheduleProvider implements ScheduleProviderInterface
{
    private Schedule $schedule;

    #[\Override]
    public function getSchedule(): Schedule
    {
        return $this->schedule ??= (new Schedule())
            ->with(
                RecurringMessage::cron(
                    '0 5 * * *',
                    new RedispatchMessage(new UpdateDataSources(), 'async')
                )
            );
    }
}
