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

namespace App\Entity;

use App\Dto\Fillable;
use App\Dto\FillTrait;
use App\Dto\Referable;
use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table('import')]
class ImportEntity implements Fillable, Referable
{
    use FillTrait;

    #[ORM\Column(type: 'uuid')]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Id]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'datetime')]
    private ?Carbon $startedAt = null;

    /**
     * Set when no task is left to run
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?Carbon $finishedAt = null;

    /**
     * Whether every task was done, set when finished; null for imports from before the graph
     */
    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $succeeded = null;

    /**
     * Sentry check-in of the scheduled update that started the import, reported when it finishes
     */
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $checkInId = null;

    #[\Override]
    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getStartedAt(): ?Carbon
    {
        return $this->startedAt;
    }

    public function setStartedAt(?Carbon $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    public function getFinishedAt(): ?Carbon
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?Carbon $finishedAt): void
    {
        $this->finishedAt = $finishedAt;
    }

    public function isSucceeded(): ?bool
    {
        return $this->succeeded;
    }

    public function setSucceeded(?bool $succeeded): void
    {
        $this->succeeded = $succeeded;
    }

    public function getCheckInId(): ?string
    {
        return $this->checkInId;
    }

    public function setCheckInId(?string $checkInId): void
    {
        $this->checkInId = $checkInId;
    }
}
