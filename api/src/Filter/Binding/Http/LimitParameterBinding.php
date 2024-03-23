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

use App\Filter\Requirement\LimitConstraint;
use Attribute;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\Schema;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use function App\Functions\clamp;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PARAMETER)]
class LimitParameterBinding implements ParameterBinding
{
    private const string LIMIT_QUERY_PARAMETER  = 'limit';
    private const string OFFSET_QUERY_PARAMETER = 'offset';

    public function __construct(
        public readonly int $defaultLimit = 20,
        public readonly int $maxLimit = 100
    ) {
    }

    #[\Override]
    public function getRequirementsFromRequest(Request $request): iterable
    {
        yield new LimitConstraint(
            offset: $request->query->get(self::OFFSET_QUERY_PARAMETER, 0),
            count: clamp($request->query->get(self::LIMIT_QUERY_PARAMETER, $this->defaultLimit), min: 0, max: $this->maxLimit),
        );
    }

    #[\Override]
    public function getDocumentation(Route $route): iterable
    {
        yield new Parameter(
            name: 'limit',
            in: 'query',
            description: 'Max number of records to obtain.',
            required: false,
            schema: new Schema(type: 'integer', maximum: $this->maxLimit, minimum: 0),
        );

        yield new Parameter(
            name: 'offset',
            in: 'query',
            description: 'Offset of the first record to obtain.',
            required: false,
            schema: new Schema(type: 'integer', minimum: 0),
        );
    }
}
