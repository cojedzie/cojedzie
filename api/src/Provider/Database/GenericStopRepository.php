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

use App\Entity\StopEntity;
use App\Filter\Handler\Database\GenericWithDatabaseHandler;
use App\Filter\Handler\Database\WithDestinationsDatabaseHandler;
use App\Filter\Requirement\Embed;
use App\Filter\Requirement\Requirement;
use App\Dto\Stop;
use App\Provider\StopRepository;
use Illuminate\Support\Collection;

class GenericStopRepository extends DatabaseRepository implements StopRepository
{
    public function all(Requirement ...$requirements): Collection
    {
        $builder = $this->em
            ->createQueryBuilder()
            ->from(StopEntity::class, 'stop')
            ->select('stop')
        ;

        return $this->allFromQueryBuilder($builder, $requirements, [
            'alias'  => 'stop',
            'entity' => StopEntity::class,
            'type'   => Stop::class,
        ]);
    }

    protected static function getHandlers()
    {
        return array_merge(parent::getHandlers(), [
            Embed::class => fn (Embed $modifier) => $modifier->getRelationship() === 'destinations'
                ? WithDestinationsDatabaseHandler::class
                : GenericWithDatabaseHandler::class,
        ]);
    }
}
