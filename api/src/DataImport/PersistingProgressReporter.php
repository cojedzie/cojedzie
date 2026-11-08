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

use App\Entity\ImportJobEntity;
use App\Entity\ImportTaskEntity;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Passes progress on to the reporter it wraps and stores it in the row of an import task or job, at most once a
 * second and always when finished. Subtasks report through the wrapped reporter only: the row holds the progress of
 * the task or job as a whole.
 */
final class PersistingProgressReporter implements ProgressReporterInterface
{
    private ?\DateTimeImmutable $lastStored = null;

    /**
     * @param class-string<ImportTaskEntity|ImportJobEntity> $entity
     */
    private function __construct(
        private readonly ProgressReporterInterface $reporter,
        private readonly EntityManagerInterface $em,
        private readonly ClockInterface $clock,
        private readonly string $entity,
        private readonly Uuid $id,
    ) {
    }

    public static function ofTask(Uuid $taskId, ProgressReporterInterface $reporter, EntityManagerInterface $em, ClockInterface $clock): self
    {
        return new self($reporter, $em, $clock, ImportTaskEntity::class, $taskId);
    }

    public static function ofJob(Uuid $jobId, ProgressReporterInterface $reporter, EntityManagerInterface $em, ClockInterface $clock): self
    {
        return new self($reporter, $em, $clock, ImportJobEntity::class, $jobId);
    }

    #[\Override]
    public function progress(float $progress, ?float $max = null, ?string $comment = null, bool $finished = false): void
    {
        $this->reporter->progress($progress, $max, $comment, $finished);

        $now = $this->clock->now();
        if ($finished || $this->isDue($now)) {
            $this->store($progress, $max, $comment);
            $this->lastStored = $now;
        }
    }

    #[\Override]
    public function milestone(string $comment, MilestoneType $type = MilestoneType::Info): void
    {
        $this->reporter->milestone($comment, $type);
    }

    #[\Override]
    public function subtask(string $name): ProgressReporterInterface
    {
        return $this->reporter->subtask($name);
    }

    private function isDue(\DateTimeImmutable $now): bool
    {
        return $this->lastStored === null || $now >= $this->lastStored->modify('+1 second');
    }

    private function store(float $progress, ?float $max, ?string $comment): void
    {
        $this->em
            ->createQuery(sprintf(
                'UPDATE %s e SET e.progress = :progress, e.progressMax = :max, e.progressComment = :comment WHERE e.id = :id',
                $this->entity,
            ))
            ->setParameter('progress', (int) $progress)
            ->setParameter('max', $max === null ? null : (int) $max)
            ->setParameter('comment', $comment === null ? null : mb_substr($comment, 0, 255))
            ->setParameter('id', $this->id, 'uuid')
            ->execute();
    }
}
