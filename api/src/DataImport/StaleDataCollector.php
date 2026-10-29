<?php
/*
 * Copyright (C) 2026 Kacper Donat
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

declare(strict_types=1);

namespace App\DataImport;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Removes imported rows that no import has written within the retention period, i.e. data that disappeared
 * upstream, and the imports nothing refers to anymore.
 *
 * Rows still used by rows that stay (a stop on a current track) are kept. Nothing is removed unless an import
 * finished within the retention period, so a stalled or failing import can't wipe the data.
 */
class StaleDataCollector
{
    /**
     * Tables in the order they are collected, children first, each with the columns that refer to its rows.
     */
    private const array TABLES = [
        'trip_stop'  => [],
        'trip'       => ['trip_stop' => 'trip_id'],
        'track_stop' => ['track' => 'final_id'],
        'track'      => [
            'trip'       => 'track_id',
            'track_stop' => 'track_id',
        ],
        'line'       => ['track' => 'line_id'],
        'stop'       => [
            'track_stop' => 'stop_id',
            'trip_stop'  => 'stop_id',
        ],
        'operator'   => [
            'line' => 'operator_id',
            'trip' => 'operator_id',
        ],
    ];

    // tables stamped with the import that last wrote their rows
    private const array STAMPED = ['provider', 'trip_stop', 'trip', 'track_stop', 'track', 'line', 'stop', 'operator'];

    public function __construct(
        private readonly Connection $connection,
        private readonly ClockInterface $clock,
        // how long rows stay after the last import that wrote them, e.g. "7 days"
        #[Autowire(env: 'STALE_DATA_RETENTION')]
        private readonly string $retention,
    ) {
    }

    /**
     * @return array<string, int> removed rows by table, empty when nothing was collected
     */
    public function collect(): array
    {
        $cutoff = $this->clock->now()->sub(\DateInterval::createFromDateString($this->retention));

        if (!$this->hasFinishedImportSince($cutoff)) {
            return [];
        }

        return $this->connection->transactional(function () use ($cutoff): array {
            $removed = [];

            foreach (self::TABLES as $table => $references) {
                $removed[$table] = $this->delete($table, 'import_id IN (SELECT id FROM import WHERE started_at < :cutoff)', $references, $cutoff);
            }

            $removed['import'] = $this->delete('import', 'started_at < :cutoff', array_fill_keys(self::STAMPED, 'import_id'), $cutoff);

            return $removed;
        });
    }

    private function hasFinishedImportSince(\DateTimeImmutable $cutoff): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM import WHERE finished_at IS NOT NULL AND started_at >= :cutoff',
            ['cutoff' => $cutoff],
            ['cutoff' => Types::DATETIME_IMMUTABLE],
        );
    }

    /**
     * @param array<string, string> $references columns of other tables referring to the table's rows, by table
     */
    private function delete(string $table, string $stale, array $references, \DateTimeImmutable $cutoff): int
    {
        $conditions = [$stale];
        foreach ($references as $referring => $column) {
            $conditions[] = sprintf('NOT EXISTS (SELECT 1 FROM %1$s WHERE %1$s.%2$s = %3$s.id)', $referring, $column, $table);
        }

        return (int) $this->connection->executeStatement(
            sprintf('DELETE FROM %s WHERE %s', $table, implode(' AND ', $conditions)),
            ['cutoff' => $cutoff],
            ['cutoff' => Types::DATETIME_IMMUTABLE],
        );
    }
}
