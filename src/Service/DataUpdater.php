<?php

namespace App\Service;

use App\Event\DataUpdateEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DataUpdater
{
    const UPDATE_EVENT = 'app.data_update';

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var EntityManagerInterface */
    private $em;

    /**
     * DataUpdater constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher, EntityManagerInterface $em)
    {
        $this->dispatcher = $dispatcher;
        $this->em = $em;
    }

    public function update()
    {
        $schema = $this->em->getConnection()->getSchemaManager();
        collect($schema->listTables())->each([$schema, 'dropAndCreateTable']);

        $this->dispatcher->dispatch(self::UPDATE_EVENT, new DataUpdateEvent());
    }
}