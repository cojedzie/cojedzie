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

use App\Dto\Referable;
use App\Filter\Requirement\RelatedFilter;
use App\Utility\RequestUtils;
use Attribute;
use JetBrains\PhpStorm\ExpectedValues;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\Schema;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use function App\Functions\setup;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class RelatedFilterParameterBinding implements ParameterBinding
{
    public readonly string $relationship;

    /**
     * @param class-string<Referable> $resource
     */
    public function __construct(
        public readonly string $resource,
        public readonly string $parameter,
        ?string $relationship = null,
        #[ExpectedValues(values: ['query', 'attributes'])]
        public readonly array $from = ['query'],
        public readonly array $documentation = [],
    ) {
        $this->relationship = $relationship ?: $this->resource;
    }

    public function getRequirementsFromRequest(Request $request): iterable
    {
        $value = RequestUtils::get($request, $this->parameter, $this->from);

        if ($value === null) {
            return;
        }

        $related = explode(',', (string) $value);
        $related = array_map($this->resource::reference(...), $related);

        yield new RelatedFilter(
            reference: $related,
            relationship: $this->relationship
        );
    }

    public function getDocumentation(Route $route): iterable
    {
        $fromAttributes = in_array('attributes', $this->from) &&
            in_array($this->parameter, $route->compile()->getPathVariables());

        $documentation = $this->documentation;
        $schema        = $documentation['schema'] ?? new Schema(type: 'string', format: 'identifier');

        yield setup(
            new Parameter(
                name: $this->parameter,
                in: $fromAttributes ? 'path' : 'query',
                explode: false,
                schema: $fromAttributes
                    ? $schema
                    : new Schema(
                        type: 'array',
                        items: setup(new Items(), function (Items $items) use ($schema) {
                            $items->mergeProperties($schema);
                        })
                    )
            ),
            function (Parameter $parameter) use ($documentation) {
                $parameter->mergeProperties((object) $documentation);
            }
        );
    }
}
