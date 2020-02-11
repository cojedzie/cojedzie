<?php

namespace App\Event;

use App\Event\HandleModifierEvent;
use App\Modifiers\Modifier;
use App\Provider\Repository;
use Doctrine\ORM\QueryBuilder;

class HandleDatabaseModifierEvent extends HandleModifierEvent
{
    private $builder;

    public function __construct(
        Modifier $modifier,
        Repository $repository,
        QueryBuilder $builder,
        array $meta = []
    ) {
        parent::__construct($modifier, $repository, $meta);

        $this->builder = $builder;
    }

    public function getBuilder(): QueryBuilder
    {
        return $this->builder;
    }

    public function replaceBuilder(QueryBuilder $builder): void
    {
        $this->builder = $builder;
    }
}
