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
use App\Filter\Requirement\Requirement;
use ReflectionAttribute;
use ReflectionParameter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use function Kadet\Functional\reflect;

class RequirementsArgumentResolver implements ArgumentResolverInterface
{
    public function __construct(
        private ArgumentResolverInterface $resolver
    ) {
    }

    public function getArguments(Request $request, callable $controller): array
    {
        $controllerReflection = reflect($controller);

        $global = $this->extractRequirementsFromReflection($controllerReflection, $request);

        foreach ($controllerReflection->getParameters() as $parameterReflection) {
            $resolved = $this->extractRequirementsFromReflection($parameterReflection, $request);

            if ($resolved !== false) {
                $this->applyRequirementToParameter($request, $parameterReflection, $resolved);
            } elseif ($global !== false) {
                $global = $this->applyRequirementToParameter($request, $parameterReflection, $global);
            }
        }

        return $this->resolver->getArguments($request, $controller);
    }

    private function applyRequirementToParameter(Request $request, ReflectionParameter $parameter, array $resolved): array|false
    {
        $types = $parameter->getType() instanceof \ReflectionNamedType
            ? [$parameter->getType()]
            : $parameter->getType()->getTypes();

        foreach ($types as $type) {
            $class = $type->getName();
            if ($class === "iterable" || $class === "array") {
                $request->attributes->set($parameter->getName(), $resolved);
                // all parameters were consumed
                return false;
            } elseif (is_subclass_of($class, Requirement::class, true)) {
                foreach ($resolved as $key => $requirement) {
                    if ($requirement instanceof $class) {
                        $request->attributes->set($parameter->getName(), $requirement);

                        unset($resolved[$key]);
                        return empty($resolved) ? false : $resolved;
                    }
                }
            }
        }

        return $resolved;
    }

    private function extractRequirementsFromReflection(\ReflectionMethod|\ReflectionParameter|\ReflectionFunction $reflection, Request $request): array|false
    {
        $attributes = $reflection->getAttributes(ParameterBinding::class, ReflectionAttribute::IS_INSTANCEOF);

        if (!$attributes) {
            return false;
        }

        $requirements = [];

        foreach ($attributes as $attribute) {
            /** @var ParameterBinding $binding */
            $binding      = $attribute->newInstance();
            $requirements = [
                ...$requirements,
                ...$binding->getRequirementsFromRequest($request),
            ];
        }

        return $requirements;
    }
}
