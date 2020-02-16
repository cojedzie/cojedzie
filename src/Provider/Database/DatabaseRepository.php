<?php

namespace App\Provider\Database;

use App\Entity\ProviderEntity;
use App\Event\HandleDatabaseModifierEvent;
use App\Event\PostProcessEvent;
use App\Exception\UnsupportedModifierException;
use App\Handler\Database\IdFilterDatabaseHandler;
use App\Handler\Database\LimitDatabaseHandler;
use App\Handler\Database\FieldFilterDatabaseHandler;
use App\Handler\PostProcessingHandler;
use App\Model\Referable;
use App\Modifier\IdFilter;
use App\Modifier\Limit;
use App\Modifier\Modifier;
use App\Modifier\FieldFilter;
use App\Provider\Repository;
use App\Service\Converter;
use App\Service\IdUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

abstract class DatabaseRepository implements ServiceSubscriberInterface, Repository
{
    /** @var EntityManagerInterface */
    protected $em;

    /** @var ProviderEntity */
    protected $provider;

    /** @var IdUtils */
    protected $id;

    /** @var Converter */
    protected $converter;

    /** @var ContainerInterface */
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
        ContainerInterface $handlers
    ) {
        $this->em        = $em;
        $this->id        = $id;
        $this->converter = $converter;
        $this->handlers  = $handlers;
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
            $handler = $this->getHandler($modifier);

            switch (true) {
                case $handler instanceof PostProcessingHandler:
                    $reducers[] = function ($result) use ($meta, $modifier, $handler) {
                        $event = new PostProcessEvent($result, $modifier, $this, array_merge([
                            'provider' => $this->provider,
                        ], $meta));

                        $handler->process($event);

                        return $event->getData();
                    };
                    break;

                default:
                    $event = new HandleDatabaseModifierEvent($modifier, $this, $builder, array_merge([
                        'provider' => $this->provider,
                    ], $meta));

                    $handler->process($event);
                    break;
            }
        }

        return collect($reducers);
    }

    protected function allFromQueryBuilder(QueryBuilder $builder, iterable $modifiers, array $meta = [])
    {
        $reducers = $this->processQueryBuilder($builder, $modifiers, $meta);

        return $reducers->reduce(function ($result, $reducer) {
            return $reducer($result);
        }, collect($builder->getQuery()->execute())->map(\Closure::fromCallable([$this, 'convert'])));
    }

    public function first(Modifier ...$modifiers)
    {
        return $this->all(Limit::count(1), ...$modifiers)->first();
    }

    protected function getHandler(Modifier $modifier)
    {
        $class = get_class($modifier);

        if (!$this->handlers->has($class)) {
            throw UnsupportedModifierException::createFromModifier($modifier, $this);
        }

        return $this->handlers->get($class);
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

    /**
     * @inheritDoc
     */
    public static function getSubscribedServices()
    {
        return array_merge([
            IdFilter::class    => IdFilterDatabaseHandler::class,
            Limit::class       => LimitDatabaseHandler::class,
            FieldFilter::class => FieldFilterDatabaseHandler::class,
        ], static::getHandlers());
    }
}
