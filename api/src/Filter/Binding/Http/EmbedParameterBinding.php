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

use App\Filter\Requirement\Embed;
use Attribute;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\Schema;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class EmbedParameterBinding implements ParameterBinding
{
    public const EMBED_QUERY_PARAMETER = 'embed';

    public function __construct(
        public readonly array $allowed
    ) {
    }

    #[\Override]
    public function getRequirementsFromRequest(Request $request): iterable
    {
        if (!$request->query->has(self::EMBED_QUERY_PARAMETER)) {
            return;
        }

        foreach (explode(',', $request->query->get(self::EMBED_QUERY_PARAMETER)) as $embedded) {
            if (!in_array($embedded, $this->allowed)) {
                continue;
            }

            yield new Embed(
                relationship: $embedded
            );
        }
    }

    // fixme: This does not work when parameter is duplicated
    #[\Override]
    public function getDocumentation(Route $route): iterable
    {
        yield new Parameter(
            name: self::EMBED_QUERY_PARAMETER,
            in: 'query',
            explode: false,
            schema: new Schema(type: "array", items: new Items(type: 'string', enum: $this->allowed))
        );
    }
}
