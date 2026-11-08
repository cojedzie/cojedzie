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

namespace App\DataImport;

/**
 * Status of a task of the import graph.
 *
 * pending → queued (dispatched) → running → done | failed; running → waiting while the jobs its importer handed out
 * run, then → running → done | failed when the last one ended (see SplittingDataImporter); pending → skipped when
 * a dependency failed or was skipped, or when a new import superseded this one.
 */
enum ImportTaskStatus: string
{
    case Pending = 'pending';
    case Queued  = 'queued';
    case Running = 'running';
    case Waiting = 'waiting';
    case Done    = 'done';
    case Failed  = 'failed';
    case Skipped = 'skipped';

    /**
     * @return list<self> statuses of a task that hasn't started running
     */
    public static function unstarted(): array
    {
        return [self::Pending, self::Queued];
    }

    /**
     * @return list<self> statuses of a task its import still waits for
     */
    public static function unfinished(): array
    {
        return [self::Pending, self::Queued, self::Running, self::Waiting];
    }

    /**
     * @return list<self> statuses of a task that ended
     */
    public static function ended(): array
    {
        return [self::Done, self::Failed, self::Skipped];
    }

    /**
     * @return list<self> statuses of a task that ended without being done, so its dependents can't run
     */
    public static function notSucceeded(): array
    {
        return [self::Failed, self::Skipped];
    }

    public function hasEnded(): bool
    {
        return in_array($this, self::ended(), true);
    }

    public function hasNotSucceeded(): bool
    {
        return in_array($this, self::notSucceeded(), true);
    }
}
