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
use App\Entity\ProviderEntity;
use App\Event\HandleDatabaseModifierEvent;
use App\Event\PostProcessEvent;
use App\Handler\Database\FieldFilterDatabaseHandler;
use App\Handler\Database\GenericWithDatabaseHandler;
use App\Handler\Database\IdFilterDatabaseHandler;
use App\Handler\Database\LimitDatabaseHandler;
use App\Handler\Database\RelatedFilterDatabaseGenericHandler;
use App\Handler\ModifierHandler;
use App\Handler\PostProcessingHandler;
use App\Model\DTO;
use App\Model\Referable;
use App\Modifier\FieldFilter;
use App\Modifier\IdFilter;
use App\Modifier\Limit;
use App\Modifier\Modifier;
use App\Modifier\RelatedFilter;
use App\Modifier\With;
use App\Provider\Repository;
use App\Service\HandlerProvider;
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

    public function __construct(
        protected EntityManagerInterface $em,
        protected IdUtils $id,
        protected Converter $converter,
        protected HandlerProvider $handlers
    ) {
        $this->handlers->loadConfiguration(array_merge([
            IdFilter::class      => IdFilterDatabaseHandler::class,
            Limit::class         => LimitDatabaseHandler::class,
            FieldFilter::class   => FieldFilterDatabaseHandler::class,
            RelatedFilter::class => RelatedFilterDatabaseGenericHandler::class,
            With::class          => GenericWithDatabaseHandler::class,
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
        return $this->converter->convert($entity, DTO::class);
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
        $query    = $builder->getQuery();

        $paginator = new Paginator($query);
        $result    = collect($paginator)->map(\Closure::fromCallable([$this, 'convert']));

        return $reducers->reduce(fn ($result, $reducer) => $reducer($result), $result);
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
