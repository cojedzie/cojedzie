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

use App\Event\DataUpdateEvent;

/**
 * An importer that splits its work into jobs, which any worker runs, and stays in control of them: it is told as each
 * job ends and decides how its node ends. Its node waits for the jobs without holding a worker, and its dependents
 * run only after the last job ended.
 */
interface SplittingDataImporter extends DataImporter
{
    /**
     * Runs one job the importer handed out with DataUpdateEvent::addJob(). Throwing fails the job, so does adding
     * jobs: only the node adds them.
     */
    public function runJob(ProgressReporterInterface $reporter, DataUpdateEvent $event, array $payload): void;

    /**
     * Called after each job of the node ends, on whichever worker ran it; for progress.
     */
    public function jobEnded(ProgressReporterInterface $reporter, ImportJobCounts $counts): void;

    /**
     * Called once, after the node's last job ended; returns whether the node succeeded.
     */
    public function jobsEnded(ProgressReporterInterface $reporter, ImportJobCounts $counts): bool;
}
