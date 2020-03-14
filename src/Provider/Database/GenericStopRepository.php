<?php

namespace App\Provider\Database;

use App\Entity\StopEntity;
use App\Handler\Database\WithDestinationsDatabaseHandler;
use App\Model\Stop;
use App\Modifier\Modifier;
use App\Modifier\With;
use App\Provider\StopRepository;
use Tightenco\Collect\Support\Collection;

class GenericStopRepository extends DatabaseRepository implements StopRepository
{
    public function all(Modifier ...$modifiers): Collection
    {
        $builder = $this->em
            ->createQueryBuilder()
            ->from(StopEntity::class, 'stop')
            ->select('stop')
        ;

        return $this->allFromQueryBuilder($builder, $modifiers, [
            'alias'  => 'stop',
            'entity' => StopEntity::class,
            'type'   => Stop::class,
        ]);
    }

    protected static function getHandlers()
    {
        return array_merge(parent::getHandlers(), [
            With::class => function (With $modifier) {
                return $modifier->getRelationship() === 'destinations'
                    ? WithDestinationsDatabaseHandler::class
                    : GenericWithHandler::class;
            },
        ]);
    }
}
