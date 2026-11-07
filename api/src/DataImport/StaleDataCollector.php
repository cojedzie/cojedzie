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

use App\Provider\ZtmGdansk\ZtmGdanskProvider;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Removes imported rows that are no longer needed: rows of versioned tables whose validity ended, schedule days
 * that passed, rows that no import has written within the retention period, i.e. data that disappeared upstream,
 * and the imports nothing refers to anymore.
 *
 * Rows still used by rows that stay (a stop on a current track) are kept. Nothing is removed while an import runs,
 * or unless an import succeeded within the retention period, so a stalled or failing import can't wipe the data.
 */
class StaleDataCollector
{
    /**
     * Tables in the order they are collected, children first, each with the columns that refer to its rows.
     */
    private const array TABLES = [
        'schedule_day' => [],
        'trip_stop'    => [],
        'trip'         => ['trip_stop' => 'trip_id'],
        'track_stop'   => ['track' => 'final_id'],
        'track'        => [
            'trip'       => 'track_id',
            'track_stop' => 'track_id',
        ],
        'line'         => ['track' => 'line_id'],
        'stop'         => [
            'track_stop' => 'stop_id',
            'trip_stop'  => 'stop_id',
        ],
        'operator'     => [
            'line' => 'operator_id',
            'trip' => 'operator_id',
        ],
    ];

    // tables stamped with the import that last wrote their rows
    private const array STAMPED = ['provider', 'schedule_day', 'trip_stop', 'trip', 'track_stop', 'track', 'line', 'stop', 'operator'];

    // tables whose rows carry their validity, collected once it ended; the retention applies only to rows without
    // an end, so a window longer than the retention keeps its future days
    private const array VERSIONED = ['trip_stop', 'trip', 'track_stop', 'track'];

    private const array PARAMETER_TYPES = [
        'now'    => Types::DATETIME_IMMUTABLE,
        'cutoff' => Types::DATETIME_IMMUTABLE,
        'today'  => Types::DATE_IMMUTABLE,
    ];

    private const string NOT_IMPORTED = 'import_id IN (SELECT id FROM import WHERE started_at < :cutoff)';

    public function __construct(
        private readonly Connection $connection,
        private readonly ClockInterface $clock,
        // how long rows stay after the last import that wrote them, e.g. "7 days"
        #[Autowire(env: 'COJEDZIE_STALE_DATA_RETENTION')]
        private readonly string $retention,
        // how long an unfinished import counts as running, e.g. "6 hours"
        #[Autowire(env: 'COJEDZIE_IMPORT_TIMEOUT')]
        private readonly string $importTimeout,
    ) {
    }

    /**
     * @return array<string, int> removed rows by table, empty when nothing was collected
     */
    public function collect(): array
    {
        $now        = \DateTimeImmutable::createFromInterface($this->clock->now())->setTimezone(new \DateTimeZone('UTC'));
        $parameters = [
            'now'    => $now,
            'cutoff' => $now->sub(\DateInterval::createFromDateString($this->retention)),
            // schedule days are days of the provider
            'today'  => new \DateTimeImmutable($now->setTimezone(new \DateTimeZone(ZtmGdanskProvider::TIMEZONE))->format('Y-m-d')),
        ];

        if ($this->isImportRunning($now) || !$this->hasSucceededImportSince($parameters['cutoff'])) {
            return [];
        }

        return $this->connection->transactional(function () use ($parameters): array {
            $removed = [];

            foreach (self::TABLES as $table => $references) {
                $stale = match (true) {
                    $table === 'schedule_day'                 => 'date < :today',
                    in_array($table, self::VERSIONED, true)   => sprintf('(valid_to < :now OR (valid_to IS NULL AND %s))', self::NOT_IMPORTED),
                    default                                   => self::NOT_IMPORTED,
                };

                $removed[$table] = $this->delete($table, $stale, $references, $parameters);
            }

            $removed['import'] = $this->delete('import', 'started_at < :cutoff', array_fill_keys(self::STAMPED, 'import_id'), $parameters);

            return $removed;
        });
    }

    private function isImportRunning(\DateTimeImmutable $now): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM import WHERE finished_at IS NULL AND started_at >= :since',
            ['since' => $now->sub(\DateInterval::createFromDateString($this->importTimeout))],
            ['since' => Types::DATETIME_IMMUTABLE],
        );
    }

    private function hasSucceededImportSince(\DateTimeImmutable $cutoff): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM import WHERE succeeded = :succeeded AND started_at >= :cutoff',
            ['succeeded' => true, 'cutoff' => $cutoff],
            ['succeeded' => Types::BOOLEAN, 'cutoff' => Types::DATETIME_IMMUTABLE],
        );
    }

    /**
     * @param array<string, string>                   $references columns of other tables referring to the table's rows, by table
     * @param array<string, \DateTimeImmutable>        $parameters of the condition, unused ones are left out
     */
    private function delete(string $table, string $stale, array $references, array $parameters): int
    {
        $conditions = [$stale];
        foreach ($references as $referring => $column) {
            $conditions[] = sprintf('NOT EXISTS (SELECT 1 FROM %1$s WHERE %1$s.%2$s = %3$s.id)', $referring, $column, $table);
        }

        $parameters = array_filter($parameters, fn (string $name) => str_contains($stale, ':' . $name), ARRAY_FILTER_USE_KEY);

        return (int) $this->connection->executeStatement(
            sprintf('DELETE FROM %s WHERE %s', $table, implode(' AND ', $conditions)),
            $parameters,
            array_intersect_key(self::PARAMETER_TYPES, $parameters),
        );
    }
}
