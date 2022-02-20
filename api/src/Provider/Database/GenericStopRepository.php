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
use App\Handler\Database\GenericWithDatabaseHandler;
use App\Handler\Database\WithDestinationsDatabaseHandler;
use App\Model\Stop;
use App\Modifier\Modifier;
use App\Modifier\With;
use App\Provider\StopRepository;
use Illuminate\Support\Collection;

class GenericStopRepository extends DatabaseRepository implements StopRepository
{
    public function all(Modifier ...$modifiers): Collection
    {
        $builder = $this->em
            ->createQueryBuilder()
            ->from(StopEntity::class, 'stop')
            ->select('stop')
        ;

        return $this->allFromQueryBuilder($builder, $modifiers, [
            'alias'  => 'stop',
            'entity' => StopEntity::class,
            'type'   => Stop::class,
        ]);
    }

    protected static function getHandlers()
    {
        return array_merge(parent::getHandlers(), [
            With::class => fn (With $modifier) => $modifier->getRelationship() === 'destinations'
                ? WithDestinationsDatabaseHandler::class
                : GenericWithDatabaseHandler::class,
        ]);
    }
}
