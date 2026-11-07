<?php
/*
 * Copyright (C) 2021 Kacper Donat
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

namespace App\Event;

use App\Entity\ImportEntity;

/**
 * What an importer's task gets: the import, and a way to split the importer's work into jobs.
 */
class DataUpdateEvent
{
    /**
     * @var list<array<string, mixed>>
     */
    private array $jobs = [];

    public function __construct(
        public readonly ImportEntity $import,
    ) {
    }

    /**
     * Hands out a job, which any worker runs with SplittingDataImporter::runJob() once the importer returns. Only a
     * SplittingDataImporter may add jobs, and only from its node, not from a job.
     *
     * @param array<string, mixed> $payload given to runJob(), stored as JSON
     */
    public function addJob(array $payload): void
    {
        $this->jobs[] = $payload;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getJobs(): array
    {
        return $this->jobs;
    }
}
