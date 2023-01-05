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

namespace App\Dto;

trait FillTrait
{
    public function fill(array $vars = [])
    {
        foreach ($vars as $name => $value) {
            switch (true) {
                case method_exists($this, $setter = 'set' . strtoupper($name)):
                    $this->{$setter}($value);
                    break;

                case property_exists($this, $name) && (new \ReflectionProperty($this, $name))->isPublic():
                    $this->$name = $value;
                    break;
            }
        }
    }

    public static function createFromArray(array $vars = [], ...$args): self
    {
        $reflection  = new \ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();

        $object = empty($args) && ($constructor && $constructor->getNumberOfRequiredParameters() > 0)
            ? $reflection->newInstanceWithoutConstructor()
            : $reflection->newInstanceArgs($args);

        $object->fill($vars);

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $object;
    }
}
