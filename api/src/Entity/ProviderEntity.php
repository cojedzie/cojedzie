<?php
/*
 * Copyright (C) 2021 Kacper Donat
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

#[ORM\Entity]
#[ORM\Table('provider')]
class ProviderEntity implements Fillable, Referable
{
    use ReferableEntityTrait, FillTrait, ImportedTrait;

    /**
     * Provider short name, for example. ZTM Gdańsk
     */
    #[ORM\Column(type: 'string')]
    private $name;

    /**
     * Class that handles that provider
     */
    #[ORM\Column(type: 'string')]
    private $class;

    /**
     * Time and date of last data update
     */
    #[ORM\Column(type: 'datetime', nullable: false)]
    private $updateDate;

    public function __construct()
    {
        $this->updateDate = Carbon::now();
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function setClass($class): void
    {
        $this->class = $class;
    }

    /**
     * @return Carbon
     */
    public function getUpdateDate()
    {
        return Carbon::instance($this->updateDate);
    }
}
