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

use App\Exception\UnsupportedModifierException;
use App\Modifier\Modifier;
use Symfony\Component\DependencyInjection\ServiceLocator;

class HandlerProvider
{
    private array $configuration = [];

    public function __construct(private readonly ServiceLocator $handlerLocator)
    {
    }

    public function loadConfiguration(array $providers)
    {
        $this->configuration = $providers;
    }

    public function get(Modifier $modifier)
    {
        $class = $modifier::class;

        if (!array_key_exists($class, $this->configuration)) {
            throw UnsupportedModifierException::createFromModifier($modifier);
        }

        $handler = $this->configuration[$class];

        if (is_callable($handler)) {
            $handler = $handler($modifier);
        }

        if (is_string($handler)) {
            return $this->handlerLocator->get($handler);
        }

        return $handler;
    }
}
