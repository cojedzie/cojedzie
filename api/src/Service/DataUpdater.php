<?php

namespace App\Service;

use App\Event\DataUpdateEvent;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\EntityManagerInterface;
use Kadet\Functional as f;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DataUpdater
{
    const UPDATE_EVENT = 'app.data_update';

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var EntityManagerInterface */
    private $em;

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
            collect($schema->listTables())
                ->reject(f\ref([$this, 'shouldTableBePreserved']))
                ->each(f\ref([$schema, 'dropAndCreateTable']))
            ;

            $this->dispatcher->dispatch(new DataUpdateEvent($output), DataUpdateEvent::NAME);

            unlink($backup);
        } catch (\Throwable $exception) {
            $connection->close();

            unlink($path);
            rename($backup, $path);

            throw $exception;
        }
    }

    private function shouldTableBePreserved(Table $schema)
    {
        return in_array($schema->getName(), ['migration_versions', 'messenger_messages'])
            || fnmatch('federated_*', $schema->getName());
    }
}
