<?php

namespace App\Filter\Handler\InMemory;

use App\Event\HandleInMemoryModifierEvent;
use App\Event\HandleModifierEvent;
use App\Filter\Handler\ModifierHandler;
use App\Filter\Requirement\FieldFilter;
use App\Filter\Requirement\FieldFilterOperator;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class FieldFilterInMemoryHandler implements ModifierHandler
{
    public function __construct(
        private readonly PropertyAccessorInterface $propertyAccessor
    ) {
    }

    public function process(HandleModifierEvent $event)
    {
        if (!$event instanceof HandleInMemoryModifierEvent) {
            return;
        }

        /** @var FieldFilter $modifier */
        $modifier = $event->getModifier();

        $field    = $modifier->getField();
        $operator = $modifier->getOperator();

        $rhs = $modifier->getValue();

        $event->addPredicate(function ($model) use ($rhs, $operator, $field) {
            $lhs = $this->propertyAccessor->getValue($model, $field);

            return match ($operator) {
                FieldFilterOperator::Equals         => $lhs == $rhs,
                FieldFilterOperator::NotEquals      => $lhs != $rhs,
                FieldFilterOperator::Less           => $lhs < $rhs,
                FieldFilterOperator::LessOrEqual    => $lhs <= $rhs,
                FieldFilterOperator::Greater        => $lhs > $rhs,
                FieldFilterOperator::GreaterOrEqual => $lhs >= $rhs,
                FieldFilterOperator::In             => in_array($lhs, $rhs),
                FieldFilterOperator::NotIn          => !in_array($lhs, $rhs),
                FieldFilterOperator::Contains       => throw new \Exception('To be implemented'),
                FieldFilterOperator::BeginsWith     => throw new \Exception('To be implemented'),
                FieldFilterOperator::EndsWith       => throw new \Exception('To be implemented'),
            };
        });
    }
}
