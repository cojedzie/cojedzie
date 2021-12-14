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

use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\EntityManagerInterface;
use Kadet\Functional as f;

class DataUpdater
{
    const UPDATE_EVENT = 'app.data_update';

    private EntityManagerInterface $em;

    /** @var iterable<DataImporter> */
    private iterable $importers;

    public function __construct(EntityManagerInterface $em, iterable $importers)
    {
        $this->em = $em;
        $this->importers = $importers;
    }

    public function update()
    {
        ini_set('memory_limit', '1G');

        $connection = $this->em->getConnection();
        $connection->getConfiguration()->setSQLLogger(null);

        /** @var DataImporter $updater */
        foreach ($this->getDataUpdatersInTopologicalOrder() as $updater) {
            $updater->import();
            gc_collect_cycles();
            echo "Memory usage: ".memory_get_usage(true).", peak: ".memory_get_peak_usage(true).PHP_EOL;
        }
    }

    /** @return \Generator<DataImporter> */
    private function getDataUpdatersInTopologicalOrder()
    {
        $nodes = [];
        $dependants = [];

        foreach ($this->importers as $importer) {
            $nodes[get_class($importer)] = [
                'value' => $importer,
                'dependencies' => $importer->getDependencies(),
            ];

            foreach ($importer->getDependencies() as $dependency) {
                if (!array_key_exists($dependency, $dependants)) {
                    $dependants[$dependency] = [];
                }

                $dependants[$dependency][] = get_class($importer);
            }
        }

        while (!empty($nodes)) {
            $next = $nodes;
            foreach ($nodes as $name => $node) {
                if (empty($node['dependencies'])) {
                    yield $node['value'];

                    foreach ($dependants[$name] ?? [] as $dependant) {
                        $next[$dependant]['dependencies'] = array_filter($nodes[$dependant]['dependencies'], fn ($item) => $item !== $name);
                    }

                    unset($next[$name]);
                }
            }
            $nodes = $next;
        }
    }
}

