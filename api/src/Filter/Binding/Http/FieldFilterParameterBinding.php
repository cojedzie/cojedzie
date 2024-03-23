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
        'contains' => FieldFilterOperator::Contains,
        'begins'   => FieldFilterOperator::BeginsWith,
        'ends'     => FieldFilterOperator::EndsWith,
    ];

    public const ORDINAL_OPERATORS = [
        ...self::EQUALITY_OPERATORS,
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

    /**
     * @psalm-param array | callable(FieldFilterOperator): array
     */
    public function __construct(
        public readonly string $parameter,
        public readonly string $field,
        public readonly FieldFilterOperator $defaultOperator = FieldFilterOperator::Equals,
        public readonly array $operators = self::DEFAULT_OPERATORS,
        public readonly mixed $documentation = [],
        public readonly array $options = [],
    ) {
    }

    #[\Override]
    public function getRequirementsFromRequest(Request $request): iterable
    {
        foreach ($request->query as $parameter => $value) {
            @[$name, $operator] = explode(':', $parameter);

            $operator = match (true) {
                is_string($operator) && array_key_exists($operator, $this->operators) => $this->operators[$operator],
                is_null($operator)                                                    => $this->defaultOperator,

                default => throw InvalidOperatorException::unsupported($operator, array_keys($this->operators), $this->parameter),
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

    #[\Override]
    public function getDocumentation(Route $route): iterable
    {
        yield setup(new Parameter(
            name: $this->parameter,
            in: 'query',
            explode: false,
            x: [
                'group' => $this->parameter,
                'alias' => [
                    sprintf('%s:%s', $this->parameter, array_search($this->defaultOperator, $this->operators)),
                ],
                'operator' => FieldFilterParameterBinding::mapOperatorToDescription($this->defaultOperator),
            ],
        ), function (Parameter $parameter) {
            $parameter->mergeProperties((object) $this->getDocumentationForOperator($this->defaultOperator));
        });

        foreach ($this->operators as $suffix => $operator) {
            if ($operator === $this->defaultOperator) {
                continue;
            }

            yield setup(
                new Parameter(
                    name: sprintf("%s:%s", $this->parameter, $suffix),
                    in: 'query',
                    explode: false,
                    x: [
                        'group'    => $this->parameter,
                        'operator' => FieldFilterParameterBinding::mapOperatorToDescription($operator),
                    ],
                ),
                function (Parameter $parameter) use ($operator) {
                    $parameter->mergeProperties((object) $this->getDocumentationForOperator($operator));
                }
            );
        }
    }

    public static function mapOperatorToDescription(FieldFilterOperator $operator): string
    {
        return match ($operator) {
            FieldFilterOperator::Equals         => 'is equal',
            FieldFilterOperator::NotEquals      => 'is not equal',
            FieldFilterOperator::Less           => 'is less than',
            FieldFilterOperator::LessOrEqual    => 'is less than or equal',
            FieldFilterOperator::Greater        => 'is greater than',
            FieldFilterOperator::GreaterOrEqual => 'is greater or equal',
            FieldFilterOperator::In             => 'is in collection',
            FieldFilterOperator::NotIn          => 'is not in collection',
            FieldFilterOperator::Contains       => 'contains substring',
            FieldFilterOperator::BeginsWith     => 'begins with substring',
            FieldFilterOperator::EndsWith       => 'ends with substring',
        };
    }

    private function getDocumentationForOperator(FieldFilterOperator $operator): array
    {
        return match (true) {
            is_callable($this->documentation) => ($this->documentation)($operator),
            is_array($this->documentation)    => $this->documentation,

            default => throw InvalidArgumentException::invalidType(
                parameter: 'documentation',
                value: $this->documentation,
                expected: ['array', 'callable(FieldFilterOperator): array']
            )
        };
    }
}
