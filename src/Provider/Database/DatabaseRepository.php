<?php

namespace App\Provider\Database;

use App\Entity\ProviderEntity;
use App\Event\HandleDatabaseModifierEvent;
use App\Exception\UnsupportedModifierException;
use App\Model\Referable;
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
        foreach ($modifiers as $modifier) {
            $event   = new HandleDatabaseModifierEvent($modifier, $this, $builder, array_merge([
                'provider' => $this->provider,
            ], $meta));

            $class = get_class($modifier);

            if (!$this->handlers->has($class)) {
                throw UnsupportedModifierException::createFromModifier($modifier, $this);
            }

            $handler = $this->handlers->get($class);

            $handler->process($event);
        }
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
        return static::getHandlers();
    }
}
