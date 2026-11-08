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

namespace App\Entity;

use App\DataImport\ImportJobStatus;
use Carbon\Carbon;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * A job an importer handed out while its node ran (see SplittingDataImporter). Workers run them as RunImportJob
 * messages, see ImportTaskRunner::runJob(). After it is persisted, its status and the columns after it change through
 * single UPDATE statements only.
 */
#[ORM\Entity]
#[ORM\Table('import_job')]
#[ORM\Index(name: 'import_job_status_idx', columns: ['task_id', 'status'])]
class ImportJobEntity
{
    #[ORM\Column(type: 'uuid')]
    #[ORM\Id]
    private Uuid $id;

    #[ORM\Column(type: Types::STRING, length: 16, enumType: ImportJobStatus::class)]
    private ImportJobStatus $status = ImportJobStatus::Queued;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?Carbon $startedAt = null; // @phpstan-ignore property.unusedType

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?Carbon $finishedAt = null; // @phpstan-ignore property.unusedType

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $error = null; // @phpstan-ignore property.unusedType

    /**
     * Last progress its importer reported, see PersistingProgressReporter
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $progress = 0;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $progressMax = null; // @phpstan-ignore property.unusedType

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $progressComment = null; // @phpstan-ignore property.unusedType

    public function __construct(
        #[ORM\ManyToOne(targetEntity: ImportTaskEntity::class)]
        #[ORM\JoinColumn(name: 'task_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        private ImportTaskEntity $task,
        /**
         * Given to SplittingDataImporter::runJob()
         */
        #[ORM\Column(type: Types::JSON)]
        private array $payload,
    ) {
        $this->id = Uuid::v7();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTask(): ImportTaskEntity
    {
        return $this->task;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getStatus(): ImportJobStatus
    {
        return $this->status;
    }

    public function getStartedAt(): ?Carbon
    {
        return $this->startedAt;
    }

    public function getFinishedAt(): ?Carbon
    {
        return $this->finishedAt;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function getProgressMax(): ?int
    {
        return $this->progressMax;
    }

    public function getProgressComment(): ?string
    {
        return $this->progressComment;
    }
}
