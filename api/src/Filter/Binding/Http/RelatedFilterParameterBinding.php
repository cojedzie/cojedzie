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

use App\Filter\Requirement\RelatedFilter;
use App\Utility\RequestUtils;
use Attribute;
use JetBrains\PhpStorm\ExpectedValues;
use Symfony\Component\HttpFoundation\Request;
use function App\Functions\encapsulate;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class RelatedFilterParameterBinding implements ParameterBinding
{
    public readonly string $relationship;

    public function __construct(
        public readonly string $resource,
        public readonly string $parameter,
        ?string $relationship = null,
        #[ExpectedValues(values: ['query', 'attributes'])]
        public readonly array $from = ['query'],
    ) {
        $this->relationship = $relationship ?: $this->resource;
    }

    public function getRequirementsFromRequest(Request $request): iterable
    {
        $value = RequestUtils::get($request, $this->parameter, $this->from);

        if ($value === null) {
            return;
        }

        $related = encapsulate($value);
        $related = collect($related)->map([$this->resource, 'reference']);

        yield new RelatedFilter(
            reference: $related,
            relationship: $this->relationship
        );
    }
}
