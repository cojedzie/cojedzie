<?php
/*
 * Copyright (C) 2022 Kacper Donat
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

namespace App\DataImport;

class ChainProgressReporter implements ProgressReporterInterface
{
    private readonly array $reporters;

    public function __construct(ProgressReporterInterface ...$reporters)
    {
        $this->reporters = $reporters;
    }

    #[\Override]
    public function progress(float $progress, float $max = null, string $comment = null, bool $finished = false): void
    {
        foreach ($this->reporters as $reporter) {
            $reporter->progress($progress, $max, $comment, $finished);
        }
    }

    #[\Override]
    public function milestone(string $comment, MilestoneType $type = MilestoneType::Info): void
    {
        foreach ($this->reporters as $reporter) {
            $reporter->milestone($comment, $type);
        }
    }

    #[\Override]
    public function subtask(string $name): ProgressReporterInterface
    {
        $reporters = array_map(
            fn (ProgressReporterInterface $reporter) => $reporter->subtask($name),
            $this->reporters
        );

        return new self(...$reporters);
    }
}
