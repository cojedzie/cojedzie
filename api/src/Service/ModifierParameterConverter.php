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

namespace App\Service;

use App\Filter\Binding\Http\ParameterBinding;
use ReflectionAttribute;
use ReflectionClass;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class ModifierParameterConverter implements ParamConverterInterface
{
    public function apply(Request $request, ParamConverter $configuration)
    {
        [ $controller, $action ] = explode('::', $request->attributes->get('_controller'));

        if (!$controller || !$action) {
            return;
        }

        $controllerReflection = new ReflectionClass($controller);
        $actionReflection     = $controllerReflection->getMethod($action);

        $requirements = [];

        foreach ($actionReflection->getAttributes(ParameterBinding::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            /** @var ParameterBinding $binding */
            $binding      = $attribute->newInstance();
            $requirements = [...$requirements, ...$binding->getRequirementsFromRequest($request)];
        }

        $request->attributes->set($configuration->getName(), $requirements);
    }

    public function supports(ParamConverter $configuration): bool
    {
        return $configuration->getName() === 'requirements';
    }
}
