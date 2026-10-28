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
    final public const string UPDATE_EVENT = 'app.data_update';

    /**
     * Measured on 2026-10-28 (a full day of ZTM data): 48 MB peak without debug, 906 MB with it, as Doctrine keeps
     * every query for the profiler. The import runs in debug through `task api:update`.
     */
    private const string MEMORY_LIMIT = '2G';

    /**
     * @param DataImporter[] $importers
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProgressReporterFactory $progressReporterFactory,
        private readonly iterable $importers
    ) {
    }

    public function update(): void
    {
        // the worker running the import keeps going afterwards, so the raised limit must not outlive the import
        $limit = ini_set('memory_limit', self::MEMORY_LIMIT);

        try {
            $this->import();
        } finally {
            // PHP refuses a limit below the memory in use; the worker's own --memory-limit then stops it instead
            $previous = ini_parse_quantity($limit);
            if ($previous === -1 || memory_get_usage(true) < $previous) {
                ini_set('memory_limit', $limit);
            }
        }
    }

    private function import(): void
    {
        // resolved before anything is written, so a dependency error doesn't leave a partial import behind
        $importers = iterator_to_array($this->getDataUpdatersInTopologicalOrder(), false);

        // create import etity
        $import = new ImportEntity();
        $import->setStartedAt(Carbon::now());

        $this->em->persist($import);
        $this->em->flush();

        $reporter = $this->progressReporterFactory->create();
        $event    = new DataUpdateEvent(import: $import);

        /** @var DataImporter $updater */
        foreach ($importers as $updater) {
            $updater->import(
                reporter: $reporter->subtask($updater->getDescription()),
                event: $event,
            );
        }

        // mark import event as finished and flush it to db
        $import->setFinishedAt(Carbon::now());
        $this->em->flush();

        $reporter->milestone(sprintf('Peak memory: %d MB', memory_get_peak_usage(true) >> 20));
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
                if (!array_key_exists((string) $dependency, $dependants)) {
                    $dependants[$dependency] = [];
                }

                $dependants[$dependency][] = $importer::class;
            }
        }

        while (!empty($nodes)) {
            if (!array_any($nodes, fn ($node) => empty($node['dependencies']))) {
                throw new \LogicException(sprintf(
                    'Importers %s depend on importers that are missing or depend on them.',
                    implode(', ', array_keys($nodes)),
                ));
            }

            $next = $nodes;
            foreach ($nodes as $name => $node) {
                if (empty($node['dependencies'])) {
                    yield $node['value'];

                    foreach ($dependants[$name] ?? [] as $dependant) {
                        $next[$dependant]['dependencies'] = array_filter($next[$dependant]['dependencies'], fn ($item) => $item !== $name);
                    }

                    unset($next[$name]);
                }
            }
            $nodes = $next;
        }
    }
}
