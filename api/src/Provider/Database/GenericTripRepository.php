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

namespace App\Provider\Database;

use App\Dto\Trip;
use App\Entity\TripEntity;
use App\Filter\Requirement\Requirement;
use App\Provider\TripRepository;
use Illuminate\Support\Collection;

class GenericTripRepository extends DatabaseRepository implements TripRepository
{
    #[\Override]
    public function all(Requirement ...$requirements): Collection
    {
        $builder = $this->em
            ->createQueryBuilder()
            ->from(TripEntity::class, 'trip')
            ->select('trip');

        return $this->allFromQueryBuilder($builder, $requirements, [
            'alias'  => 'trip',
            'entity' => TripEntity::class,
            'type'   => Trip::class,
        ]);
    }
}
