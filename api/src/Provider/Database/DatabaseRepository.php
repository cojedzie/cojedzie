<?php
/*
 * Copyright (C) 2021 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Provider\Database;

use App\DataConverter\Converter;
use App\Dto\Dto;
use App\Dto\Referable;
use App\Entity\ProviderEntity;
use App\Event\HandleDatabaseModifierEvent;
use App\Event\PostProcessEvent;
use App\Filter\Handler\Database\FieldFilterDatabaseHandler;
use App\Filter\Handler\Database\GenericWithDatabaseHandler;
use App\Filter\Handler\Database\IdFilterDatabaseHandler;
use App\Filter\Handler\Database\LimitDatabaseHandler;
use App\Filter\Handler\Database\RelatedFilterDatabaseGenericHandler;
use App\Filter\Handler\ModifierHandler;
use App\Filter\Handler\PostProcessingHandler;
use App\Filter\Requirement\Embed;
use App\Filter\Requirement\FieldFilter;
use App\Filter\Requirement\IdConstraint;
use App\Filter\Requirement\LimitConstraint;
use App\Filter\Requirement\RelatedFilter;
use App\Filter\Requirement\Requirement;
use App\Provider\Repository;
use App\Service\HandlerProvider;
use App\Service\HandlerProviderFactory;
use App\Service\IdUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

abstract class DatabaseRepository implements Repository
{
    final public const DEFAULT_LIMIT = 100;

    /**
     * @var ProviderEntity
     */
    protected $provider;
    protected readonly HandlerProvider $handlers;

    public function __construct(
        protected EntityManagerInterface $em,
        protected IdUtils $id,
        protected Converter $converter,
        HandlerProviderFactory $handlerProviderFactory
    ) {
        $this->handlers = $handlerProviderFactory->createHandlerProvider(array_merge([
            IdConstraint::class    => IdFilterDatabaseHandler::class,
            LimitConstraint::class => LimitDatabaseHandler::class,
            FieldFilter::class     => FieldFilterDatabaseHandler::class,
            RelatedFilter::class   => RelatedFilterDatabaseGenericHandler::class,
            Embed::class           => GenericWithDatabaseHandler::class,
        ], static::getHandlers()));
    }

    /**
     * @return static
     */
    public function withProvider(ProviderEntity $provider)
    {
        $result           = clone $this;
        $result->provider = $provider;

        return $result;
    }

    protected function convert($entity)
    {
        return $this->converter->convert($entity, Dto::class);
    }

    protected function reference($class, Referable $referable)
    {
        $id = $this->id->generate($this->provider, $referable->getId());

        return $this->em->getReference($class, $id);
    }

    protected function processQueryBuilder(QueryBuilder $builder, iterable $requirements, array $meta = [])
    {
        $reducers = [];

        foreach ($requirements as $modifier) {
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

    protected function allFromQueryBuilder(QueryBuilder $builder, iterable $requirements, array $meta = [])
    {
        $builder->setMaxResults(self::DEFAULT_LIMIT);

        $reducers = $this->processQueryBuilder($builder, $requirements, $meta);
        $query    = $builder->getQuery();

        $paginator = new Paginator($query);
        $result    = collect($paginator)->map(\Closure::fromCallable([$this, 'convert']));

        return $reducers->reduce(fn ($result, $reducer) => $reducer($result), $result);
    }

    abstract public function all(Requirement ...$requirements);

    public function first(Requirement ...$requirements)
    {
        return $this->all(LimitConstraint::count(1), ...$requirements)->first();
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
