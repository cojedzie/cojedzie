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
use App\Entity\ImportEntity;
use App\Event\DataUpdateEvent;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;

class DataUpdater
{
    final public const UPDATE_EVENT = 'app.data_update';

    /**
     * @param DataImporter[] $importers
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProgressReporterFactory $progressReporterFactory,
        private readonly iterable $importers
    ) {
    }

    public function update()
    {
        ini_set('memory_limit', '4G');

        // create import etity
        $import = new ImportEntity();
        $import->setStartedAt(Carbon::now());

        $this->em->persist($import);
        $this->em->flush();

        // disable SQL logging to prevent performance issues
        $connection = $this->em->getConnection();
        $connection->getConfiguration()->setSQLLogger(null);

        $reporter = $this->progressReporterFactory->create();
        $event    = new DataUpdateEvent(import: $import);

        /** @var DataImporter $updater */
        foreach ($this->getDataUpdatersInTopologicalOrder() as $updater) {
            $updater->import(
                reporter: $reporter->subtask($updater->getDescription()),
                event: $event,
            );

            // force garbage collection
            gc_collect_cycles();
        }

        // mark import event as finished and flush it to db
        $import->setFinishedAt(Carbon::now());
        $this->em->flush();
    }

    /**
     * @return \Generator<DataImporter>
     */
    private function getDataUpdatersInTopologicalOrder()
    {
        $nodes      = [];
        $dependants = [];

        foreach ($this->importers as $importer) {
            $nodes[$importer::class] = [
                'value'        => $importer,
                'dependencies' => $importer->getDependencies(),
            ];

            foreach ($importer->getDependencies() as $dependency) {
                if (!array_key_exists($dependency, $dependants)) {
                    $dependants[$dependency] = [];
                }

                $dependants[$dependency][] = $importer::class;
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
