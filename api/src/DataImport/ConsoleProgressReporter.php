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

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class ConsoleProgressReporter implements ProgressReporterInterface
{
    private ?ProgressBar $progressBar = null;

    public function __construct(private InputInterface $input, private ConsoleOutputInterface $output, private int $depth = 0)
    {
    }

    public function progress(float $progress, float $max = null, string $comment = null, bool $finished = false): void
    {
        $max = (int)$max;

        if (!isset($this->progressBar)) {
            $this->progressBar = new ProgressBar($this->output->section(), $max);
            $this->updateProgressBarMessage();
        }

        if ($this->progressBar->getMaxSteps() !== $max) {
            $this->progressBar->setMaxSteps($max);
            $this->updateProgressBarMessage();
        }

        if ($comment !== @$this->progressBar->getMessage()) {
            $this->progressBar->setMessage($comment);
            $this->updateProgressBarMessage();
        }

        $this->progressBar->setProgress($progress);

        if ($finished) {
            $this->progressBar->finish();
        }
    }

    public function milestone(string $comment, MilestoneType $type = MilestoneType::Info): void
    {
        [ $icon, $color ] = match ($type) {
            MilestoneType::Warning => ['⚠️', 'yellow'],
            MilestoneType::Success => ['✔', 'bright-green'],
            MilestoneType::Error   => ['✖', 'red'],
            default => [ '•', null ],
        };

        $comment = sprintf('%s %s', $icon, $comment);

        $message = sprintf(
            '%s %s',
            $this->indentation(),
            $color ? sprintf('<fg=%s>%s</>', $color, $comment) : $comment,
        );

        $this->output->section()->writeln($message);
    }

    public function subtask(string $name): ProgressReporterInterface
    {
        $section = $this->output->section();
        $section->writeln(sprintf('<fg=%s>%s</>', $this->getSectionColor(), $this->depth ? $this->indentation().'• '.$name : $name));

        return new ConsoleProgressReporter(
            $this->input,
            $this->output,
            $this->depth + 1,
        );
    }

    private function updateProgressBarMessage()
    {
        $format = $this->indentation();

        if ($this->progressBar->getMaxSteps()) {
            $format .= '[%bar%] %current%/%max% %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%';
        } else {
            $format .= '[%bar%] %current% %elapsed:6s% %memory:6s%';
        }

        if (@$this->progressBar->getMessage()) {
            $format .= ' %message%';
        }

        $this->progressBar->setFormat($format);
    }

    private function indentation()
    {
        return str_repeat('  ', $this->depth);
    }

    private function getSectionColor()
    {
        return match (true) {
            $this->depth === 0 => 'green',
            $this->depth === 1 => 'bright-green',
            $this->depth === 2 => 'bright-yellow',
            $this->depth >= 3  => 'yellow'
        };
    }
}
