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

use Kadet\Functional\Collections as c;

/**
 * Jobs of a node of the import graph, by how they ended so far. Jobs a new import skipped count as failed.
 */
final readonly class ImportJobCounts
{
    public function __construct(
        public int $total,
        public int $done,
        public int $failed,
    ) {
    }

    /**
     * @param array<string, int> $statuses number of jobs by status
     */
    public static function fromStatuses(array $statuses): self
    {
        return new self(
            total: array_sum($statuses),
            done: $statuses[ImportJobStatus::Done->value] ?? 0,
            failed: ImportJobStatus::notSucceeded()
                |> c\sum(fn (ImportJobStatus $status) => $statuses[$status->value] ?? 0),
        );
    }

    public function ended(): int
    {
        return $this->done + $this->failed;
    }

    public function remaining(): int
    {
        return $this->total - $this->ended();
    }
}
