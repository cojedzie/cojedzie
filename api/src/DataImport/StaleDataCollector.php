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
 * Removes imported rows that are no longer needed: rows of versioned tables whose validity ended more than a grace
 * period ago, rows that no
 * import has written within the retention period, i.e. data that disappeared upstream, and the imports nothing
 * refers to anymore. Tables of a single provider are left to its importers.
 *
 * Rows still used by rows that stay (a stop on a current track) are kept. Nothing is removed while an import runs,
 * or unless an import succeeded within the retention period, so a stalled or failing import can't wipe the data.
 */
class StaleDataCollector
{
    /**
     * Tables in the order they are collected, children first, each with the columns of other tables that refer to
     * its rows: a table and its column referring to the row's id, or also the row's column it refers to.
     */
    private const array TABLES = [
        'trip_stop'    => [],
        'trip'         => [['trip_stop', 'trip_id']],
        'track_stop'   => [
            ['track', 'origin_id'],
            ['track', 'final_id'],
            // a track stays while a trip runs on it, and keeps its stops
            ['trip', 'track_id', 'track_id'],
        ],
        'track'        => [
            ['trip', 'track_id'],
            ['track_stop', 'track_id'],
        ],
        'line'         => [['track', 'line_id']],
        'stop'         => [
            ['track_stop', 'stop_id'],
            ['trip_stop', 'stop_id'],
        ],
        'operator'     => [
            ['line', 'operator_id'],
            ['trip', 'operator_id'],
        ],
    ];

    // tables stamped with the import that last wrote their rows
    private const array STAMPED = ['provider', 'trip_stop', 'trip', 'track_stop', 'track', 'line', 'stop', 'operator'];

    // tables whose rows carry their validity, collected once it ended more than the grace ago; the retention applies
    // only to rows without an end, so a window longer than the retention keeps its future days
    private const array VERSIONED = ['trip_stop', 'trip', 'track_stop', 'track'];

    private const array PARAMETER_TYPES = [
        'expired' => Types::DATETIME_IMMUTABLE,
        'cutoff'  => Types::DATETIME_IMMUTABLE,
    ];

    private const string OLD_IMPORT = 'started_at < :cutoff';

    // rows that no import has written within the retention period
    private const string NOT_IMPORTED = 'import_id IN (SELECT id FROM import WHERE ' . self::OLD_IMPORT . ')';

    public function __construct(
        private readonly Connection $connection,
        private readonly ClockInterface $clock,
        // how long rows stay after the last import that wrote them, e.g. "7 days"
        #[Autowire(env: 'COJEDZIE_STALE_DATA_RETENTION')]
        private readonly string $retention,
        // how long versioned rows stay after their validity ended, e.g. "1 day": slack for a late collection, a
        // clock or a time zone off, and for rows imported with a shorter validity
        #[Autowire(env: 'COJEDZIE_STALE_DATA_GRACE')]
        private readonly string $grace,
        // how long an unfinished import counts as running, e.g. "1 hour"
        #[Autowire(env: 'COJEDZIE_IMPORT_TIMEOUT')]
        private readonly string $importTimeout,
    ) {
    }

    /**
     * @return array<string, int> removed rows by table, empty when nothing was collected
     */
    public function collect(): array
    {
        $now    = \DateTimeImmutable::createFromInterface($this->clock->now())->setTimezone(new \DateTimeZone('UTC'));
        $cutoff = $now->sub(\DateInterval::createFromDateString($this->retention));

        if ($this->isImportRunning($now) || !$this->hasSucceededImportSince($cutoff)) {
            return [];
        }

        return $this->connection->transactional(fn () => $this->removeStaleRows([
            'expired' => $now->sub(\DateInterval::createFromDateString($this->grace)),
            'cutoff'  => $cutoff,
        ]));
    }

    /**
     * @param array<string, \DateTimeImmutable> $parameters of the stale conditions
     *
     * @return array<string, int> removed rows by table
     */
    private function removeStaleRows(array $parameters): array
    {
        $removed = [];
        foreach (self::TABLES as $table => $references) {
            $removed[$table] = $this->delete($table, self::staleCondition($table), $references, $parameters);
        }

        $removed['import'] = $this->delete(
            'import',
            self::OLD_IMPORT,
            array_map(fn (string $table) => [$table, 'import_id'], self::STAMPED),
            $parameters,
        );

        return $removed;
    }

    private static function staleCondition(string $table): string
    {
        return match (true) {
            in_array($table, self::VERSIONED, true) => sprintf(
                '(valid_to < :expired OR (valid_to IS NULL AND %s))',
                self::NOT_IMPORTED,
            ),
            default                                 => self::NOT_IMPORTED,
        };
    }

    private function isImportRunning(\DateTimeImmutable $now): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM import WHERE status = :running AND started_at >= :since',
            [
                'running' => ImportStatus::Running->value,
                'since'   => $now->sub(\DateInterval::createFromDateString($this->importTimeout)),
            ],
            ['since' => Types::DATETIME_IMMUTABLE],
        );
    }

    private function hasSucceededImportSince(\DateTimeImmutable $cutoff): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM import WHERE status = :succeeded AND started_at >= :cutoff',
            ['succeeded' => ImportStatus::Succeeded->value, 'cutoff' => $cutoff],
            ['cutoff' => Types::DATETIME_IMMUTABLE],
        );
    }

    /**
     * Deletes the stale rows of the table that no other table refers to.
     *
     * @param list<array{0: string, 1: string, 2?: string}> $references columns of other tables referring to the table's rows
     * @param array<string, \DateTimeImmutable>             $parameters of the condition, unused ones are left out
     */
    private function delete(string $table, string $stale, array $references, array $parameters): int
    {
        $conditions = [$stale];
        foreach ($references as $reference) {
            $conditions[] = self::notReferencedBy($table, ...$reference);
        }

        $parameters = self::usedIn($stale, $parameters);

        return (int) $this->connection->executeStatement(
            sprintf('DELETE FROM %s WHERE %s', $table, implode(' AND ', $conditions)),
            $parameters,
            array_intersect_key(self::PARAMETER_TYPES, $parameters),
        );
    }

    /**
     * @param string $column   of the referring table
     * @param string $referred the row's column it refers to
     */
    private static function notReferencedBy(
        string $table,
        string $referring,
        string $column,
        string $referred = 'id',
    ): string {
        return sprintf(
            'NOT EXISTS (SELECT 1 FROM %1$s WHERE %1$s.%2$s = %3$s.%4$s)',
            $referring,
            $column,
            $table,
            $referred,
        );
    }

    /**
     * @param array<string, \DateTimeImmutable> $parameters
     *
     * @return array<string, \DateTimeImmutable> the parameters the condition uses
     */
    private static function usedIn(string $condition, array $parameters): array
    {
        return array_filter(
            $parameters,
            fn (string $name) => str_contains($condition, ':' . $name),
            ARRAY_FILTER_USE_KEY,
        );
    }
}
