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

namespace App\Filter\Handler\Database;

use App\Event\HandleDatabaseModifierEvent;
use App\Event\HandleModifierEvent;
use App\Filter\Handler\ModifierHandler;
use App\Filter\Requirement\FieldFilter;
use App\Filter\Requirement\FieldFilterOperator;
use App\Model\ScheduledStop;
use App\Model\Stop;
use App\Model\TrackStop;
use function App\Functions\encapsulate;

class FieldFilterDatabaseHandler implements ModifierHandler
{
    protected $mapping = [
        Stop::class => [
            'name' => 'name',
        ],
        ScheduledStop::class => [
            'departure' => 'departure',
            'arrival'   => 'arrival',
        ],
        TrackStop::class => [
            'order' => 'order',
        ],
    ];

    public function process(HandleModifierEvent $event)
    {
        if (!$event instanceof HandleDatabaseModifierEvent) {
            return;
        }

        /** @var FieldFilter $modifier */
        $modifier = $event->getModifier();
        $builder  = $event->getBuilder();
        $alias    = $event->getMeta()['alias'];

        $field    = $this->mapFieldName($event->getMeta()['type'], $modifier->getField());
        $operator = $modifier->getOperator();
        $value    = $modifier->getValue();

        $parameter = sprintf(":%s_%s", $alias, $field);

        if ($operator->isSetOperator()) {
            $parameter = "($parameter)";
            $value     = encapsulate($value);
        }

        $where = "{$alias}.{$field}";

        if (!$modifier->isCaseSensitive()) {
            $where = sprintf('LOWER(%s)', $where);
            $value = is_array($value) ? array_map(fn ($x) => mb_strtolower($x), $value) : mb_strtolower($value);
        }

        $value = match ($operator) {
            FieldFilterOperator::Contains   => "%$value%",
            FieldFilterOperator::BeginsWith => "$value%",
            FieldFilterOperator::EndsWith   => "%$value",
            default                         => $value,
        };

        $builder
            ->andWhere(sprintf("%s %s %s", $where, $this->mapFieldFilterOperatorToDatabase($operator), $parameter))
            ->setParameter($parameter, $value)
        ;
    }

    protected function mapFieldName(string $class, string $field): string
    {
        if (!isset($this->mapping[$class][$field])) {
            throw new \InvalidArgumentException(
                sprintf("Unable to map field %s of %s into entity field.", $field, $class)
            );
        }

        return $this->mapping[$class][$field];
    }

    protected function mapFieldFilterOperatorToDatabase(FieldFilterOperator $operator): string
    {
        return match ($operator) {
            // Equality
            FieldFilterOperator::Equals    => '=',
            FieldFilterOperator::NotEquals => '!=',
            // Ordinal
            FieldFilterOperator::Less           => '<',
            FieldFilterOperator::LessOrEqual    => '<=',
            FieldFilterOperator::Greater        => '>',
            FieldFilterOperator::GreaterOrEqual => '>=',
            // Set
            FieldFilterOperator::In    => 'in',
            FieldFilterOperator::NotIn => 'not in',
            // String
            FieldFilterOperator::Contains,
            FieldFilterOperator::BeginsWith,
            FieldFilterOperator::EndsWith => 'LIKE',
        };
    }
}
