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

use App\Filter\Binding\Http\Exception\InvalidOperatorException;
use App\Filter\Requirement\FieldFilter;
use App\Filter\Requirement\FieldFilterOperator;
use Attribute;
use OpenApi\Attributes\Parameter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use function App\Functions\setup;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class FieldFilterParameterBinding implements ParameterBinding
{
    public const EQUALITY_OPERATORS = [
        'eq'  => FieldFilterOperator::Equals,
        'not' => FieldFilterOperator::NotEquals,
    ];

    public const SET_OPERATORS = [
        'in'     => FieldFilterOperator::In,
        'not-in' => FieldFilterOperator::NotIn,
    ];

    public const STRING_OPERATORS = [
        ...self::EQUALITY_OPERATORS,
        ...self::SET_OPERATORS,
        'contains' => FieldFilterOperator::Contains,
        'begins'   => FieldFilterOperator::BeginsWith,
        'ends'     => FieldFilterOperator::EndsWith,
    ];

    public const ORDINAL_OPERATORS = [
        ...self::EQUALITY_OPERATORS,
        ...self::SET_OPERATORS,
        'eq'  => FieldFilterOperator::Equals,
        'not' => FieldFilterOperator::NotEquals,
        'le'  => FieldFilterOperator::Less,
        'leq' => FieldFilterOperator::LessOrEqual,
        'ge'  => FieldFilterOperator::Greater,
        'geq' => FieldFilterOperator::GreaterOrEqual,
    ];

    public const DEFAULT_OPERATORS = [
        ...self::ORDINAL_OPERATORS,
        ...self::STRING_OPERATORS,
    ];

    public function __construct(
        public readonly string $parameter,
        public readonly string $field,
        public readonly FieldFilterOperator $defaultOperator = FieldFilterOperator::Equals,
        public readonly array $operators = self::DEFAULT_OPERATORS,
        public readonly array $documentation = [],
        public readonly array $options = [],
    ) {
    }

    public function getRequirementsFromRequest(Request $request): iterable
    {
        foreach ($request->query as $parameter => $value) {
            @[ $name, $operator ] = explode(':', $parameter);

            $operator = match (true) {
                is_string($operator) && array_key_exists($operator, $this->operators) => $this->operators[$operator],
                is_null($operator) => $this->defaultOperator,
                default            => throw InvalidOperatorException::unsupported($operator, array_keys($this->operators), $this->parameter),
            };

            if ($name === $this->parameter) {
                yield new FieldFilter(
                    field: $this->field,
                    value: $request->query->get($parameter),
                    operator: $operator,
                    options: $this->options,
                );
            }
        }
    }

    public function getDocumentation(Route $route): iterable
    {
        yield setup(new Parameter(
            name: $this->parameter,
            in: 'query',
            x: [
                'operators' => array_map($this->mapOperatorToDescription(...), $this->operators),
            ],
        ), function (Parameter $parameter) {
            $parameter->mergeProperties((object) $this->documentation);
        });
    }

    private function mapOperatorToDescription(FieldFilterOperator $operator): string
    {
        return match ($operator) {
            FieldFilterOperator::Equals         => 'equals',
            FieldFilterOperator::NotEquals      => 'not equals',
            FieldFilterOperator::Less           => 'less',
            FieldFilterOperator::LessOrEqual    => 'less or equal',
            FieldFilterOperator::Greater        => 'greater',
            FieldFilterOperator::GreaterOrEqual => 'greater or equal',
            FieldFilterOperator::In             => 'in collection',
            FieldFilterOperator::NotIn          => 'not in collection)',
            FieldFilterOperator::Contains       => 'contains substring',
            FieldFilterOperator::BeginsWith     => 'begins with substring',
            FieldFilterOperator::EndsWith       => 'ends with substring',
        };
    }
}
