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
use App\Filter\Handler\Database\FieldFilterDatabaseHandler;
use App\Filter\Handler\Database\GenericWithDatabaseHandler;
use App\Filter\Handler\Database\IdFilterDatabaseHandler;
use App\Filter\Handler\Database\LimitDatabaseHandler;
use App\Filter\Handler\Database\RelatedFilterDatabaseGenericHandler;
use App\Filter\Handler\ModifierHandler;
use App\Filter\Handler\PostProcessingHandler;
use App\Filter\Modifier\EmbedModifier;
use App\Filter\Modifier\FieldFilterModifier;
use App\Filter\Modifier\IdFilterModifier;
use App\Filter\Modifier\LimitModifier;
use App\Filter\Modifier\Modifier;
use App\Filter\Modifier\RelatedFilterModifier;
use App\Model\DTO;
use App\Model\Referable;
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
            IdFilterModifier::class      => IdFilterDatabaseHandler::class,
            LimitModifier::class         => LimitDatabaseHandler::class,
            FieldFilterModifier::class   => FieldFilterDatabaseHandler::class,
            RelatedFilterModifier::class => RelatedFilterDatabaseGenericHandler::class,
            EmbedModifier::class         => GenericWithDatabaseHandler::class,
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

    abstract public function all(Modifier ...$modifiers);

    public function first(Modifier ...$modifiers)
    {
        return $this->all(LimitModifier::count(1), ...$modifiers)->first();
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
