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

use App\DataImport\ImportTaskStatus;
use Carbon\Carbon;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * A node of an import: one importer. Workers run them as RunImportTask messages, see ImportGraph; the importer may
 * split its work into jobs (ImportJobEntity).
 */
#[ORM\Entity]
#[ORM\Table('import_task')]
#[ORM\Index(name: 'import_task_status_idx', columns: ['import_id', 'status'])]
class ImportTaskEntity
{
    #[ORM\Column(type: 'uuid')]
    #[ORM\Id]
    private Uuid $id;

    #[ORM\Column(type: Types::STRING, length: 16, enumType: ImportTaskStatus::class)]
    private ImportTaskStatus $status = ImportTaskStatus::Pending;

    // the status and the columns below change through single UPDATE statements, see ImportGraph

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?Carbon $startedAt = null; // @phpstan-ignore property.unusedType

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?Carbon $finishedAt = null; // @phpstan-ignore property.unusedType

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $error = null; // @phpstan-ignore property.unusedType

    /**
     * @param list<string> $dependencies
     */
    public function __construct(
        #[ORM\ManyToOne(targetEntity: ImportEntity::class)]
        #[ORM\JoinColumn(name: 'import_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        private ImportEntity $import,
        /**
         * Service id of the importer, its class
         */
        #[ORM\Column(type: Types::STRING)]
        private string $importer,
        #[ORM\Column(type: Types::STRING)]
        private string $description,
        /**
         * Ids of the tasks that must be done first
         */
        #[ORM\Column(type: Types::JSON)]
        private array $dependencies = [],
    ) {
        $this->id = Uuid::v7();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getImport(): ImportEntity
    {
        return $this->import;
    }

    public function getImporter(): string
    {
        return $this->importer;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStatus(): ImportTaskStatus
    {
        return $this->status;
    }

    /**
     * @return list<string>
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
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
}
