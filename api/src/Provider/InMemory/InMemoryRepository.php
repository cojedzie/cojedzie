<?php

namespace App\Provider\InMemory;

use App\Event\HandleInMemoryRequirementEvent;
use App\Event\PostProcessEvent;
use App\Filter\Handler\Common\RefsEmbedHandler;
use App\Filter\Handler\InMemory\FieldFilterInMemoryHandler;
use App\Filter\Handler\InMemory\LimitInMemoryHandler;
use App\Filter\Handler\ModifierHandler;
use App\Filter\Handler\PostProcessingHandler;
use App\Filter\Requirement\Embed;
use App\Filter\Requirement\FieldFilter;
use App\Filter\Requirement\LimitConstraint;
use App\Filter\Requirement\Requirement;
use App\Provider\FluentRepository;
use App\Service\HandlerProvider;
use App\Service\HandlerProviderFactory;
use Ds\Seq;
use Kadet\Functional as f;
use Kadet\Functional\Collections as c;

abstract class InMemoryRepository implements FluentRepository
{
    protected readonly HandlerProvider $handlers;

    public function __construct(
        HandlerProviderFactory $handlerProviderFactory
    ) {
        $this->handlers = $handlerProviderFactory->createHandlerProvider(array_merge([
            LimitConstraint::class => LimitInMemoryHandler::class,
            FieldFilter::class     => FieldFilterInMemoryHandler::class,
            Embed::class           => RefsEmbedHandler::class,
        ], static::getHandlers()));
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

    #[\Override]
    abstract public function all(Requirement ...$requirements): Seq;

    #[\Override]
    public function first(Requirement ...$requirements)
    {
        return $this->all(LimitConstraint::count(1), ...$requirements) |> c\first();
    }

    protected function filterAndProcessResults(Seq $result, array $requirements): Seq
    {
        $reducers = [];
        $filters  = [];

        foreach ($requirements as $modifier) {
            $handler = $this->handlers->get($modifier);

            $event = new HandleInMemoryRequirementEvent($modifier, $this);

            if ($handler instanceof ModifierHandler) {
                $handler->process($event);

                $filters = [...$filters, $event->getPredicates()];
            }

            if ($handler instanceof PostProcessingHandler) {
                $reducers[] = function (Seq $result) use ($modifier, $handler) {
                    $event = new PostProcessEvent($result, $modifier, $this);

                    $handler->postProcess($event);

                    return $event->getData();
                };
            }
        }

        $result = $result |> c\filter(f\all(...$filters));

        return $reducers |> c\reduce(fn (Seq $result, \Closure $reducer) => $reducer($result), $result);
    }
}
