<?php

namespace App\Provider\Database;

use App\Entity\LineEntity;
use App\Event\HandleDatabaseModifierEvent;
use App\Handlers\Database\LimitDatabaseHandler;
use App\Handlers\Database\WithIdDatabaseHandler;
use App\Handlers\ModifierHandler;
use App\Model\Line;
use App\Modifiers\Limit;
use App\Modifiers\WithId;
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
        return $this->first(new WithId($id));
    }

    public function getManyById($ids): Collection
    {
        return $this->all(new WithId($ids));
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

        foreach ($modifiers as $modifier) {
            $event   = new HandleDatabaseModifierEvent($modifier, $this, $builder, [
                'alias'    => 'line',
                'provider' => $this->provider,
            ]);

            $handler = $this->getHandlers()[get_class($modifier)];

            $handler->process($event);
        }

        return collect($builder->getQuery()->execute())->map(f\ref([$this, 'convert']));
    }

    /** @return ModifierHandler[] */
    private function getHandlers()
    {
        return [
            WithId::class => new WithIdDatabaseHandler($this->id),
            Limit::class  => new LimitDatabaseHandler(),
        ];
    }
}
