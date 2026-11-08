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

use App\Dto\TimetableEntry;
use App\Entity\TripStopEntity;
use App\Filter\Requirement\Requirement;
use App\Provider\TimetableRepository;
use Ds\Seq;

class GenericTimetableRepository extends DatabaseRepository implements TimetableRepository
{
    #[\Override]
    public function all(Requirement ...$requirements): Seq
    {
        $builder = $this->em
            ->createQueryBuilder()
            ->select('trip_stop')
            ->from(TripStopEntity::class, 'trip_stop')
            ->orderBy('trip_stop.departure', 'ASC')
        ;

        return $this->allFromQueryBuilder($builder, $requirements, [
            'alias'  => 'trip_stop',
            'type'   => TimetableEntry::class,
            'entity' => TripStopEntity::class,
        ]);
    }
}
