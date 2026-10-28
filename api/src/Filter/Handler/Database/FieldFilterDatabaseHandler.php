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

use App\Dto\ScheduledStop;
use App\Dto\Stop;
use App\Dto\TrackStop;
use App\Event\HandleDatabaseRequirementEvent;
use App\Event\HandleRequirementEvent;
use App\Filter\Handler\ModifierHandler;
use App\Filter\Requirement\FieldFilter;
use App\Filter\Requirement\FieldFilterOperator;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use function App\Functions\encapsulate;

class FieldFilterDatabaseHandler implements ModifierHandler
{
    private const string LIKE_ESCAPE = '!';

    private const array PATTERN_OPERATORS = [
        FieldFilterOperator::Contains,
        FieldFilterOperator::BeginsWith,
        FieldFilterOperator::EndsWith,
    ];
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

    #[\Override]
    public function process(HandleRequirementEvent $event)
    {
        if (!$event instanceof HandleDatabaseRequirementEvent) {
            return;
        }

        /** @var FieldFilter $modifier */
        $modifier = $event->getRequirement();
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

        if (!$modifier->isCaseSensitive() && $this->isTextField($builder, $event->getMeta()['entity'], $field)) {
            $where = sprintf('LOWER(%s)', $where);
            $value = is_array($value) ? array_map(fn ($x) => mb_strtolower((string) $x), $value) : mb_strtolower((string) $value);
        }

        $pattern = in_array($operator, self::PATTERN_OPERATORS, true);

        if ($pattern) {
            // wildcard characters in the searched value are meant literally
            $value = str_replace(
                [self::LIKE_ESCAPE, '%', '_'],
                [self::LIKE_ESCAPE . self::LIKE_ESCAPE, self::LIKE_ESCAPE . '%', self::LIKE_ESCAPE . '_'],
                (string) $value
            );
        }

        $value = match ($operator) {
            FieldFilterOperator::Contains   => "%$value%",
            FieldFilterOperator::BeginsWith => "$value%",
            FieldFilterOperator::EndsWith   => "%$value",
            default                         => $value,
        };

        $builder
            ->andWhere(sprintf(
                "%s %s %s%s",
                $where,
                $this->mapFieldFilterOperatorToDatabase($operator),
                $parameter,
                $pattern ? sprintf(" ESCAPE '%s'", self::LIKE_ESCAPE) : ''
            ))
            ->setParameter($parameter, $value)
        ;
    }

    /**
     * Only text can be matched case-insensitively. LOWER() of a timestamp or an integer doesn't exist on Postgres, and
     * lowercasing a date turns it into a string that skips its conversion to the database time zone.
     */
    private function isTextField(QueryBuilder $builder, string $entity, string $field): bool
    {
        $type = $builder->getEntityManager()->getClassMetadata($entity)->getTypeOfField($field);

        return in_array($type, [Types::STRING, Types::TEXT, Types::ASCII_STRING], true);
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
