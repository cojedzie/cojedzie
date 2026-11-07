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

namespace App\Entity\ZtmGdansk;

use App\Dto\Fillable;
use App\Dto\FillTrait;
use App\Entity\LineEntity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Remembers that the ZTM Gdańsk schedule of a line on a day was imported, also when the day had no trips, so it isn't
 * downloaded again. Written in the transaction that writes the day's trips, see ZtmGdanskScheduleDataImporter, which
 * also removes the days that passed.
 *
 * It refers to no import: the stale data collector removes imports nothing refers to, and a day must stay recorded
 * until it passes, whichever import wrote it.
 */
#[ORM\Entity]
#[ORM\Table('ztm_gdansk_schedule_day')]
#[ORM\UniqueConstraint(name: 'ztm_gdansk_schedule_day_line_date_idx', columns: ['line_id', 'date'])]
class ZtmGdanskScheduleDayEntity implements Fillable
{
    use FillTrait;

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

    public function getImportedAt(): \DateTimeImmutable
    {
        return $this->importedAt;
    }
}
