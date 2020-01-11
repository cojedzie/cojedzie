<?php

namespace App\Service;

use App\Event\DataUpdateEvent;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

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

    public function update(OutputInterface $output = null)
    {
        $connection = $this->em->getConnection();
        $connection->getConfiguration()->setSQLLogger(null);
        $schema     = $connection->getSchemaManager();

        $path   = preg_replace("~sqlite:///~si", '', $connection->getParams()['path']);
        $backup = "$path.backup";

        copy($path, $backup);

        try {
            collect($schema->listTables())->reject(function (Table $schema) {
                return $schema->getName() === 'migration_versions';
            })->each([$schema, 'dropAndCreateTable']);

            $this->dispatcher->dispatch(new DataUpdateEvent($output), DataUpdateEvent::NAME);
            unlink($backup);
        } catch (\Throwable $exception) {
            $connection->close();

            unlink($path);
            rename($backup, $path);

            throw $exception;
        }
    }
}
