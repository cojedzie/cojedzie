<?php

namespace App\Provider\Database;

use App\Entity\LineEntity;
use App\Model\Line;
use App\Modifier\Modifier;
use App\Provider\LineRepository;
use Illuminate\Support\Collection;

class GenericLineRepository extends DatabaseRepository implements LineRepository
{
    public function all(Modifier ...$modifiers): Collection
    {
        $builder = $this->em
            ->createQueryBuilder()
            ->from(LineEntity::class, 'line')
            ->select('line')
        ;

        return $this->allFromQueryBuilder($builder, $modifiers, [
            'alias'  => 'line',
            'entity' => LineEntity::class,
            'type'   => Line::class,
        ]);
    }
}
