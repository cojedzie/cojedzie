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

use App\Dto\Fillable;
use App\Dto\FillTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Remembers that the schedule of a line on a day was imported, also when the day had no trips, so it isn't
 * downloaded again. A line+day without a row has no trips.
 */
#[ORM\Entity]
#[ORM\Table('schedule_day')]
#[ORM\UniqueConstraint(name: 'schedule_day_line_date_idx', columns: ['line_id', 'date'])]
class ScheduleDayEntity implements Fillable
{
    use FillTrait, ProviderReferenceTrait;

    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: LineEntity::class)]
    #[ORM\JoinColumn(name: 'line_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private LineEntity $line;

    /**
     * Service date, in the provider's timezone
     */
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $date;

    /**
     * Import that downloaded the day; the row goes with it, as nothing else refers to the import then
     */
    #[ORM\ManyToOne(targetEntity: ImportEntity::class)]
    #[ORM\JoinColumn(name: 'import_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ImportEntity $import;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $importedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLine(): LineEntity
    {
        return $this->line;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getImport(): ImportEntity
    {
        return $this->import;
    }

    public function getImportedAt(): \DateTimeImmutable
    {
        return $this->importedAt;
    }
}
