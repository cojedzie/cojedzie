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

namespace App\Provider\Dummy;

use App\Model\Stop;
use App\Modifier\Modifier;
use App\Provider\StopRepository;
use App\Service\Proxy\ReferenceFactory;
use Illuminate\Support\Collection;
use Kadet\Functional as f;

class DummyStopRepository implements StopRepository
{
    private $reference;

    /**
     * DummyDepartureProviderRepository constructor.
     *
     * @param $reference
     */
    public function __construct(ReferenceFactory $reference)
    {
        $this->reference = $reference;
    }

    public function getAll(): Collection
    {
        return collect();
    }

    public function getById($id): ?Stop
    {
        return Stop::createFromArray(['id' => $id, 'name' => 'lorem']);
    }

    public function getManyById($ids): Collection
    {
        return collect($ids)->map(f\ref([ $this, 'getById' ]));
    }

    public function findByName(string $name): Collection
    {
        return collect();
    }

    public function first(Modifier ...$modifiers)
    {
        // TODO: Implement first() method.
    }

    public function all(Modifier ...$modifiers): Collection
    {
        // TODO: Implement all() method.
    }
}
