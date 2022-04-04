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

namespace App\Describer;

use App\Filter\Binding\Http\ParameterBinding;
use App\Utility\CollectionUtils;
use Ds\Sequence;
use Ds\Set;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberInterface;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberTrait;
use OpenApi\Annotations\OpenApi;
use OpenApi\Annotations\Parameter;
use OpenApi\Context;
use OpenApi\Generator;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Routing\Route;

#[AutoconfigureTag('nelmio_api_doc.route_describer')]
class ParameterBindingRouteDescriber implements RouteDescriberInterface
{
    use RouteDescriberTrait;

    public function describe(OpenApi $api, Route $route, \ReflectionMethod $reflectionMethod)
    {
        $bindings      = $this->getAllParameterBindingsFromAction($reflectionMethod);
        $documentation = $this->getDocumentationForParameterBindings($bindings, $route);

        if ($documentation->isEmpty()) {
            return;
        }

        foreach ($this->getOperations($api, $route) as $operation) {
            foreach ($documentation as $item) {
                if ($item instanceof Parameter) {
                    $parameter = Util::getOperationParameter($operation, $item->name, $item->in);
                    $parameter->mergeProperties($item);
                }
            }
        }
    }

    private function getDocumentationForParameterBindings(iterable $bindings, Route $route): Sequence
    {
        $context            = Generator::$context;
        Generator::$context = new Context();

        $documentation = CollectionUtils::flatMap($bindings, fn (ParameterBinding $binding) => yield from $binding->getDocumentation($route));

        Generator::$context = $context;

        return $documentation;
    }

    private function getAllParameterBindingsFromAction(\ReflectionMethod $actionReflection): Set
    {
        $bindings = new Set();

        $bindings->add(
            ...array_map(
                fn ($attribute) => $attribute->newInstance(),
                $actionReflection->getAttributes(ParameterBinding::class, \ReflectionAttribute::IS_INSTANCEOF)
            )
        );

        foreach ($actionReflection->getParameters() as $parameterReflection) {
            $bindings->add(
                ...array_map(
                    fn ($attribute) => $attribute->newInstance(),
                    $parameterReflection->getAttributes(ParameterBinding::class, \ReflectionAttribute::IS_INSTANCEOF)
                )
            );
        }

        return $bindings;
    }
}
