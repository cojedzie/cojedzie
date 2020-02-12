<?php

namespace App\Provider\Database;

use App\Entity\LineEntity;
use App\Event\HandleDatabaseModifierEvent;
use App\Handlers\Database\LimitDatabaseHandler;
use App\Handlers\Database\IdFilterDatabaseHandler;
use App\Handlers\ModifierHandler;
use App\Model\Line;
use App\Modifiers\Limit;
use App\Modifiers\IdFilter;
use App\Provider\LineRepository;
use App\Modifiers\Modifier;
use Tightenco\Collect\Support\Collection;
use Kadet\Functional as f;

class GenericLineRepository extends DatabaseRepository implements LineRepository
{
    public function getAll(): Collection
    {
        return $this->all();
    }

    public function getById($id): ?Line
    {
        return $this->first(new IdFilter($id));
    }

    public function getManyById($ids): Collection
    {
        return $this->all(new IdFilter($ids));
    }

    public function first(Modifier ...$modifiers)
    {
        return $this->all(Limit::count(1), ...$modifiers)->first();
    }

    public function all(Modifier ...$modifiers)
    {
        $builder = $this->em
            ->createQueryBuilder()
            ->from(LineEntity::class, 'line')
            ->select('line')
        ;

        $this->processQueryBuilder($builder, $modifiers, [
            'alias'  => 'line',
            'entity' => LineEntity::class,
            'type'   => Line::class,
        ]);

        return collect($builder->getQuery()->execute())->map(f\ref([$this, 'convert']));
    }

    /** @return ModifierHandler[] */
    protected static function getHandlers()
    {
        return [
            IdFilter::class => IdFilterDatabaseHandler::class,
            Limit::class    => LimitDatabaseHandler::class,
        ];
    }
}
