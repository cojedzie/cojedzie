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

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LoggerProgressReporter implements ProgressReporterInterface
{
    private const PROGRESS_REPORT_MINIMAL_INTERVAL = 5;
    private float $lastProgressReported            = 0;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $name = ""
    ) {
    }

    public function progress(float $progress, float $max = null, string $comment = null, bool $finished = false): void
    {
        if (!$finished && !$this->shouldLogProgressMessage()) {
            return;
        }

        if ($max) {
            $message = sprintf("%.2f/%.2f (%.2f%%)", $progress, $max, $progress / $max * 100);
        } else {
            $message = sprintf("%.2f", $progress);
        }

        if ($comment) {
            $message = sprintf("%s (%s)", $message, $comment);
        }

        $this->logger->info(sprintf("%s: %s", $this->name, $message));

        $this->lastProgressReported = microtime(true);
    }

    public function milestone(string $comment, MilestoneType $type = MilestoneType::Info): void
    {
        $this->logger->log(
            match ($type) {
                MilestoneType::Warning => LogLevel::WARNING,
                MilestoneType::Error   => LogLevel::ERROR,
                MilestoneType::Success => LogLevel::INFO,
                MilestoneType::Info    => LogLevel::DEBUG,
            },
            sprintf("%s: %s", $this->name, $comment)
        );
    }

    public function subtask(string $name): ProgressReporterInterface
    {
        return new self(
            $this->logger,
            trim(sprintf("%s / %s", $this->name, $name), ' /')
        );
    }

    private function shouldLogProgressMessage()
    {
        $secondsFromLastProgressReport = microtime(true) - $this->lastProgressReported;

        return $secondsFromLastProgressReport > self::PROGRESS_REPORT_MINIMAL_INTERVAL;
    }
}
