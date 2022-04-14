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

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;

trait ReferableTrait
{
    /**
     * Identifier coming from provider service
     *
     * @noRector Rector\Php81\Rector\Property\ReadOnlyPropertyRector
     */
    #[Serializer\Type('string')]
    #[Serializer\Groups(['Default', 'Reference', 'Minimal'])]
    private string $id;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public static function reference($id)
    {
        if (!is_array($id)) {
            $id = ['id' => $id];
        }

        $result = new static();
        $result->fill($id);

        return $result;
    }
}
