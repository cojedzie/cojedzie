<?php

namespace App\Provider\Database;

use App\Entity\OperatorEntity;
use App\Model\Operator;
use App\Modifier\Modifier;
use App\Provider\OperatorRepository;
use Tightenco\Collect\Support\Collection;

class GenericOperatorRepository extends DatabaseRepository implements OperatorRepository
{
    public function all(Modifier ...$modifiers): Collection
    {
        $builder = $this->em
            ->createQueryBuilder()
            ->from(OperatorEntity::class, 'operator')
            ->select('operator')
        ;

        return $this->allFromQueryBuilder($builder, $modifiers, [
            'alias'  => 'operator',
            'entity' => OperatorEntity::class,
            'type'   => Operator::class,
        ]);
    }
}
