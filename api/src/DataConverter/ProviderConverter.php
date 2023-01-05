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

namespace App\DataConverter;

use App\Dto\Dto;
use App\Dto\Provider as ProviderDTO;
use App\Provider\Provider;

class ProviderConverter implements Converter
{
    public function convert($entity, string $type)
    {
        /** @var Provider $entity */

        return ProviderDTO::createFromArray([
            'id'          => $entity->getIdentifier(),
            'shortName'   => $entity->getShortName(),
            'name'        => $entity->getName(),
            'attribution' => $entity->getAttribution(),
            'lastUpdate'  => $entity->getLastUpdate() ? clone $entity->getLastUpdate() : null,
            'location'    => $entity->getLocation(),
        ]);
    }

    public function supports($entity, string $type)
    {
        return $entity instanceof Provider
            && ($type === Dto::class || is_subclass_of($type, Dto::class, true));
    }
}
