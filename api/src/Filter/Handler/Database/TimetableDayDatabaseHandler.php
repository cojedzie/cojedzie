<?php
/*
 * Copyright (C) 2026 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace App\Filter\Handler\Database;

use App\Event\HandleDatabaseRequirementEvent;
use App\Event\HandleRequirementEvent;
use App\Filter\Handler\ModifierHandler;
use App\Filter\Requirement\DayMode;
use App\Filter\Requirement\TimetableDay;
use Carbon\Carbon;
use Doctrine\DBAL\Types\Types;
use Psr\Clock\ClockInterface;

/**
 * Timetable entries of a calendar day (departure between the local midnights of the date) or of a service day (the
 * trip's service date), or both. The date is in the time zone given in the `timezone` meta key.
 */
class TimetableDayDatabaseHandler implements ModifierHandler
{
    public function __construct(
        private readonly ClockInterface $clock,
    ) {
    }

    #[\Override]
    public function process(HandleRequirementEvent $event)
    {
        if (!$event instanceof HandleDatabaseRequirementEvent) {
            return;
        }

        /** @var TimetableDay $day */
        $day      = $event->getRequirement();
        $builder  = $event->getBuilder();
        $alias    = $event->getMeta()['alias'];
        $timezone = $event->getMeta()['timezone']
            ?? throw new \LogicException('Timetable days need the provider\'s time zone.');

        $date = $day->date ?? $this->clock->now()->setTimezone($timezone)->format('Y-m-d');

        // own alias, so the filter doesn't depend on joins made for embeds or by the repository
        $trip = "{$alias}_day_trip";

        $calendar = "{$alias}.departure >= :{$alias}_day_start AND {$alias}.departure < :{$alias}_day_end";
        $service  = "{$trip}.serviceDate = :{$alias}_day_service";

        if ($day->mode !== DayMode::Calendar) {
            $builder
                ->join("{$alias}.trip", $trip)
                ->setParameter("{$alias}_day_service", new \DateTimeImmutable($date), Types::DATE_IMMUTABLE)
            ;
        }

        if ($day->mode !== DayMode::Service) {
            // local midnights, so a day over a DST change lasts 23 or 25 hours
            $start = Carbon::createFromFormat('!Y-m-d', $date, $timezone);
            $end   = $start->clone()->addDay();

            $builder
                ->setParameter("{$alias}_day_start", $start->utc(), Types::DATETIME_MUTABLE)
                ->setParameter("{$alias}_day_end", $end->utc(), Types::DATETIME_MUTABLE)
            ;
        }

        $builder->andWhere(match ($day->mode) {
            DayMode::Calendar => $calendar,
            DayMode::Service  => $service,
            DayMode::Extended => "({$calendar}) OR ({$service})",
        });
    }
}
