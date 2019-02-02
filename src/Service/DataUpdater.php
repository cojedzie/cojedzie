<?php

namespace App\Service;

use App\Event\DataUpdateEvent;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\EntityManagerInterface;
use function Sodium\add;
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
        $connection = $this->em->getConnection();
        $connection->getConfiguration()->setSQLLogger(null);
        $schema     = $connection->getSchemaManager();

        $path   = $connection->getParams()['path'];
        $backup = "$path.backup";

        copy($path, $backup);

        try {
            collect($schema->listTables())->reject(function (Table $schema) {
                return $schema->getName() === 'migration_versions';
            })->each([$schema, 'dropAndCreateTable']);

            $this->dispatcher->dispatch(self::UPDATE_EVENT, new DataUpdateEvent());
        } catch (\Throwable $exception) {
            $connection->close();

            unlink($path);
            rename($backup, $path);
        } finally {
            unlink($backup);
        }
    }
}