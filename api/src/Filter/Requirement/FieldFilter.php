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

namespace App\Filter\Requirement;

use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldFilter implements Requirement
{
    public const OPTION_CASE_SENSITIVE = 'case_sensitive';
    private readonly array $options;

    public function __construct(
        private readonly string $field,
        private $value,
        private readonly FieldFilterOperator $operator = FieldFilterOperator::Equals,
        array $options = [],
    ) {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('case_sensitive', false);
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getOperator(): FieldFilterOperator
    {
        return $this->operator;
    }

    public function isCaseSensitive(): bool
    {
        return $this->options[self::OPTION_CASE_SENSITIVE] ?? false;
    }
}
