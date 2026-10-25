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

declare(strict_types=1);

namespace App\Tests\Filter\Binding\Http;

use App\Filter\Binding\Http\FieldFilterParameterBinding;
use App\Filter\Requirement\FieldFilterOperator;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\Schema;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;

final class FieldFilterParameterBindingTest extends TestCase
{
    public function testSchemaGivenAsArrayIsDocumentedAsSchema(): void
    {
        $binding = new FieldFilterParameterBinding(
            parameter: 'order',
            field: 'order',
            operators: ['eq' => FieldFilterOperator::Equals],
            documentation: ['schema' => ['type' => 'integer',
                'minimum'                       => 0]],
        );

        /** @var Parameter $parameter */
        $parameter = [...$binding->getDocumentation(new Route('/'))][0];

        $this->assertInstanceOf(Schema::class, $parameter->schema);
        $this->assertSame('integer', $parameter->schema->type);
        $this->assertSame(0, $parameter->schema->minimum);
    }

    public function testArraySchemaItemsAreDocumentedAsItems(): void
    {
        $binding = new FieldFilterParameterBinding(
            parameter: 'type',
            field: 'type',
            operators: ['eq' => FieldFilterOperator::Equals],
            documentation: ['schema' => ['type' => 'array',
                'items'                         => ['type' => 'string',
                    'enum'                                 => ['info']]]],
        );

        /** @var Parameter $parameter */
        $parameter = [...$binding->getDocumentation(new Route('/'))][0];

        $this->assertInstanceOf(Items::class, $parameter->schema->items);
        $this->assertSame(['info'], $parameter->schema->items->enum);
    }
}
