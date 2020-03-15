<?php

namespace App\Provider\Database;

use App\Entity\ProviderEntity;
use App\Event\HandleDatabaseModifierEvent;
use App\Event\PostProcessEvent;
use App\Handler\Database\FieldFilterDatabaseHandler;
use App\Handler\Database\IdFilterDatabaseHandler;
use App\Handler\Database\LimitDatabaseHandler;
use App\Handler\Database\RelatedFilterDatabaseGenericHandler;
use App\Handler\Database\GenericWithDatabaseHandler;
use App\Handler\ModifierHandler;
use App\Handler\PostProcessingHandler;
use App\Model\Referable;
use App\Modifier\FieldFilter;
use App\Modifier\IdFilter;
use App\Modifier\Limit;
use App\Modifier\Modifier;
use App\Modifier\RelatedFilter;
use App\Modifier\With;
use App\Provider\Repository;
use App\Service\Converter;
use App\Service\HandlerProvider;
use App\Service\IdUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

abstract class DatabaseRepository implements Repository
{
    const DEFAULT_LIMIT = 100;

    /** @var EntityManagerInterface */
    protected $em;

    /** @var ProviderEntity */
    protected $provider;

    /** @var IdUtils */
    protected $id;

    /** @var Converter */
    protected $converter;

    /** @var HandlerProvider */
    protected $handlers;

    /**
     * DatabaseRepository constructor.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(
        EntityManagerInterface $em,
        IdUtils $id,
        Converter $converter,
        HandlerProvider $handlers
    ) {
        $this->em        = $em;
        $this->id        = $id;
        $this->converter = $converter;
        $this->handlers  = $handlers;

        $this->handlers->loadConfiguration(array_merge([
            IdFilter::class      => IdFilterDatabaseHandler::class,
            Limit::class         => LimitDatabaseHandler::class,
            FieldFilter::class   => FieldFilterDatabaseHandler::class,
            RelatedFilter::class => RelatedFilterDatabaseGenericHandler::class,
            With::class          => GenericWithDatabaseHandler::class,
        ], static::getHandlers()));
    }

    /** @return static */
    public function withProvider(ProviderEntity $provider)
    {
        $result           = clone $this;
        $result->provider = $provider;

        return $result;
    }

    protected function convert($entity)
    {
        return $this->converter->convert($entity);
    }

    protected function reference($class, Referable $referable)
    {
        $id = $this->id->generate($this->provider, $referable->getId());

        return $this->em->getReference($class, $id);
    }

    protected function processQueryBuilder(QueryBuilder $builder, iterable $modifiers, array $meta = [])
    {
        $reducers = [];

        foreach ($modifiers as $modifier) {
            $handler = $this->handlers->get($modifier);

            if ($handler instanceof ModifierHandler) {
                $event = new HandleDatabaseModifierEvent($modifier, $this, $builder, array_merge([
                    'provider' => $this->provider,
                ], $meta));

                $handler->process($event);
            }

            if ($handler instanceof PostProcessingHandler) {
                $reducers[] = function ($result) use ($meta, $modifier, $handler) {
                    $event = new PostProcessEvent($result, $modifier, $this, array_merge([
                        'provider' => $this->provider,
                    ], $meta));

                    $handler->postProcess($event);

                    return $event->getData();
                };
            }

        }

        return collect($reducers);
    }

    protected function allFromQueryBuilder(QueryBuilder $builder, iterable $modifiers, array $meta = [])
    {
        $builder->setMaxResults(self::DEFAULT_LIMIT);

        $reducers = $this->processQueryBuilder($builder, $modifiers, $meta);
        $result   = collect($builder->getQuery()->execute())->map(\Closure::fromCallable([$this, 'convert']));

        return $reducers->reduce(function ($result, $reducer) {
            return $reducer($result);
        }, $result);
    }

    public function first(Modifier ...$modifiers)
    {
        return $this->all(Limit::count(1), ...$modifiers)->first();
    }

    /**
     * Returns array describing handlers for each modifier type. Syntax is as follows:
     * [ IdFilter::class => IdFilterDatabaseHandler::class ]
     *
     * It is internally used as part of service subscriber.
     *
     * @return array
     */
    protected static function getHandlers()
    {
        return [];
    }
}
