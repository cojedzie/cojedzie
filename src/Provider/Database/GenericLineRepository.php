<?php

namespace App\Provider\Database;

use App\Entity\LineEntity;
use App\Event\HandleDatabaseModifierEvent;
use App\Handler\Database\LimitDatabaseHandler;
use App\Handler\Database\IdFilterDatabaseHandler;
use App\Handler\ModifierHandler;
use App\Model\Line;
use App\Modifier\Limit;
use App\Modifier\IdFilter;
use App\Provider\LineRepository;
use App\Modifier\Modifier;
use Tightenco\Collect\Support\Collection;
use Kadet\Functional as f;

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
