<?php
/*
 * Copyright (C) 2022 Kacper Donat
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

namespace App\Filter\Binding\Http;

use App\Exception\InvalidArgumentException;
use Attribute;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ParameterBindingProvider implements ParameterBinding, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function __construct(
        private readonly mixed $source
    ) {
    }

    public function getModifiersFromRequest(Request $request): iterable
    {
        yield from match (true) {
            is_callable($this->source)           => ($this->source)()->getModifiersFromRequest($request),
            class_exists($this->source)          => (new $this->source())->getModifiersFromRequest($request),
            $this->container->has($this->source) => $this->container->get($this->source)->getModifiersFromRequest($request),
            default                              => InvalidArgumentException::invalidType('source', $this->source, ['class-name', 'service id', 'callable']),
        };
    }
}
