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

use App\Filter\Requirement\FieldFilterOperator;
use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class ImportFilterParameterBinding extends FieldFilterParameterBinding
{
    public function __construct()
    {
        parent::__construct(
            parameter: 'import',
            field: 'import',
            operators: FieldFilterParameterBinding::EQUALITY_OPERATORS,
            documentation: fn (FieldFilterOperator $operator) => [
                'description' => match ($operator) {
                    FieldFilterOperator::Equals    => 'Select records created or updated with specific import',
                    FieldFilterOperator::NotEquals => 'Select records not created or updated with specific import',
                    default                        => throw new \Exception('Unexpected match value')
                },
                'required' => false,
                'schema'   => [
                    'type'   => 'string',
                    'format' => 'uuid',
                ],
            ]
        );
    }
}
