<?php

namespace App\Provider\Database;

use App\Entity\StopEntity;
use App\Handler\Database\IncludeDestinationsDatabaseHandler;
use App\Model\Stop;
use App\Modifier\Modifier;
use App\Modifier\IncludeDestinations;
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
            IncludeDestinations::class => IncludeDestinationsDatabaseHandler::class,
        ]);
    }
}
