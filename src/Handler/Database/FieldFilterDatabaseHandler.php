<?php

namespace App\Handler\Database;

use App\Event\HandleDatabaseModifierEvent;
use App\Event\HandleModifierEvent;
use App\Handler\ModifierHandler;
use App\Model\Stop;
use App\Modifier\FieldFilter;
use function App\Functions\encapsulate;

class FieldFilterDatabaseHandler implements ModifierHandler
{
    protected $mapping = [
        Stop::class => [
            'name' => 'name',
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

        if ($operator === 'in' || $operator === 'not in') {
            $parameter = "($parameter)";
            $value     = encapsulate($value);
        }

        $builder
            ->andWhere(sprintf("%s.%s %s %s", $alias, $field, $operator, $parameter))
            ->setParameter($parameter, $value)
        ;
    }

    protected function mapFieldName(string $class, string $field)
    {
        if (!isset($this->mapping[$class][$field])) {
            throw new \InvalidArgumentException(
                sprintf("Unable to map field %s of %s into entity field.", $field, $class)
            );
        }

        return $this->mapping[$class][$field];
    }
}
