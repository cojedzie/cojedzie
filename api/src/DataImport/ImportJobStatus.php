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
 * Status of a job an importer handed out, see SplittingDataImporter.
 *
 * queued (dispatched) → running → done | failed; queued → skipped when a new import superseded this one.
 */
enum ImportJobStatus: string
{
    case Queued  = 'queued';
    case Running = 'running';
    case Done    = 'done';
    case Failed  = 'failed';
    case Skipped = 'skipped';

    /**
     * @return list<self> statuses of a job its task still waits for
     */
    public static function unended(): array
    {
        return [self::Queued, self::Running];
    }

    /**
     * @return list<self> statuses of a job that ended
     */
    public static function ended(): array
    {
        return [self::Done, self::Failed, self::Skipped];
    }

    /**
     * @return list<self> statuses of a job that ended without being done; a skipped job counts as failed
     */
    public static function notSucceeded(): array
    {
        return [self::Failed, self::Skipped];
    }

    public function hasEnded(): bool
    {
        return in_array($this, self::ended(), true);
    }
}
