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

namespace App\Service;

use App\Entity\Entity;
use App\Entity\ProviderEntity;

class IdUtils
{
    const DELIMITER = '::';

    public function generate(ProviderEntity $provider, $id)
    {
        // todo: use array cache if not fast enough
        return sprintf('%s%s%s', $provider->getId(), self::DELIMITER, $id);
    }

    public function strip($id)
    {
        return explode(self::DELIMITER, $id)[1];
    }

    public function of(Entity $entity)
    {
        return $this->strip($entity->getId());
    }
}
