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
