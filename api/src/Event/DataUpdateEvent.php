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

namespace App\Event;

use App\Entity\ImportEntity;

/**
 * What an importer's task gets: the import, the task's payload, and a way to add tasks to the import.
 */
class DataUpdateEvent
{
    /**
     * @var list<array{importer: string, payload: array, description: string}>
     */
    private array $spawned = [];

    /**
     * @param ?array $payload data of a spawned task, null for the importer's own task
     */
    public function __construct(
        public readonly ImportEntity $import,
        public readonly ?array $payload = null,
    ) {
    }

    /**
     * Adds a task to the import, run once the importer returns. It has no dependencies and nothing depends on it,
     * and its failure doesn't fail the import.
     *
     * @param string $importer service id (class) of the importer to run with the payload
     */
    public function spawn(string $importer, array $payload, string $description): void
    {
        $this->spawned[] = ['importer' => $importer, 'payload' => $payload, 'description' => $description];
    }

    /**
     * @return list<array{importer: string, payload: array, description: string}>
     */
    public function getSpawned(): array
    {
        return $this->spawned;
    }
}
