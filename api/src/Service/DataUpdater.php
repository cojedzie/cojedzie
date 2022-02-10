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

use App\DataImport\DataImporter;
use App\DataImport\ProgressReporterFactory;
use App\DataImport\SectionalProgressReporterInterface;
use Doctrine\ORM\EntityManagerInterface;

class DataUpdater
{
    const UPDATE_EVENT = 'app.data_update';

    private EntityManagerInterface $em;
    private ProgressReporterFactory $progressReporterFactory;
    /** @var iterable<DataImporter> */
    private iterable $importers;

    public function __construct(EntityManagerInterface $em, ProgressReporterFactory $progressReporterFactory, iterable $importers)
    {
        $this->em = $em;
        $this->progressReporterFactory = $progressReporterFactory;
        $this->importers = $importers;
    }

    public function update()
    {
        ini_set('memory_limit', '1G');

        $connection = $this->em->getConnection();
        $connection->getConfiguration()->setSQLLogger(null);
        $reporter = $this->progressReporterFactory->create();

        /** @var DataImporter $updater */
        foreach ($this->getDataUpdatersInTopologicalOrder() as $updater) {
            $updater->import($reporter->subtask($updater->getDescription()));
            gc_collect_cycles();
        }
    }

    /** @return \Generator<DataImporter> */
    private function getDataUpdatersInTopologicalOrder()
    {
        $nodes = [];
        $dependants = [];

        foreach ($this->importers as $importer) {
            $nodes[get_class($importer)] = [
                'value'        => $importer,
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

