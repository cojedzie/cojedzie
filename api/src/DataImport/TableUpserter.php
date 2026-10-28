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

use App\Entity\ImportEntity;
use App\Utility\IterableUtils;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Ds\Set;

/**
 * Writes imported rows of a provider: inserts the ones that don't exist yet and updates those that do.
 */
final readonly class TableUpserter
{
    private const int BATCH_SIZE = 100;

    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * Inserts rows that don't exist yet and updates those that do, stamping provider_id and import_id. Opens no
     * transaction, the caller decides its boundary.
     *
     * @param iterable<string, array<string, mixed>> $rows  keyed by id
     * @param array<string, string|ParameterType>    $types column types, import_id is added
     *
     * @return int number of rows written
     */
    public function upsert(
        string $table,
        iterable $rows,
        string $providerId,
        ImportEntity $import,
        array $types = [],
        ?ProgressReporterInterface $reporter = null,
    ): int {
        $types = [
            ...$types,
            'import_id' => 'uuid',
        ];

        $query = $this->connection->createQueryBuilder()
            ->from($table, 't')
            ->select('id')
            ->where('t.provider_id = :provider_id')
            ->andWhere('t.id IN (:ids)')
            ->setParameter('provider_id', $providerId);

        $count = 0;
        foreach (IterableUtils::batch($rows, self::BATCH_SIZE) as $batch) {
            if ($batch === []) {
                continue;
            }

            $query->setParameter('ids', array_keys($batch), ArrayParameterType::STRING);
            $existing = new Set($query->executeQuery()->iterateColumn());

            foreach ($batch as $id => $row) {
                $row = [
                    ...$row,
                    'import_id' => $import->getId(),
                ];

                if ($existing->contains($id)) {
                    $this->connection->update($table, $row, [
                        'id'          => $id,
                        'provider_id' => $providerId,
                    ], $types);
                } else {
                    $this->connection->insert($table, [
                        'id'          => $id,
                        'provider_id' => $providerId,
                        ...$row,
                    ], $types);
                }
            }

            $count += count($batch);
            $reporter?->progress($count);
        }

        return $count;
    }
}
