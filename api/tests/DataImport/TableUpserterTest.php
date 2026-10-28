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

namespace App\Tests\DataImport;

use App\DataImport\TableUpserter;
use App\Event\DataUpdateEvent;

final class TableUpserterTest extends ImporterTestCase
{
    public function testInsertsNewAndUpdatesExistingRows(): void
    {
        $this->connection->insert('operator', [
            'id'          => 'trojmiasto::1',
            'provider_id' => self::PROVIDER,
            'name'        => 'Old',
        ]);
        $event = $this->newImport();

        $count = $this->upsert($event, [
            'trojmiasto::1' => [
                'name' => 'Updated',
            ],
            'trojmiasto::2' => [
                'name' => 'Inserted',
            ],
        ]);

        $import = $event->import->getId()->toBinary();

        $this->assertSame(2, $count);
        $this->assertSame([
            ['trojmiasto::1', 'trojmiasto', $import, 'Updated'],
            ['trojmiasto::2', 'trojmiasto', $import, 'Inserted'],
        ], $this->connection->fetchAllNumeric('SELECT id, provider_id, import_id, name FROM operator ORDER BY id'));
    }

    public function testEmptyFeedWritesNothing(): void
    {
        $this->assertSame(0, $this->upsert($this->newImport(), []));
        $this->assertSame([], $this->rows('operator', 'id'));
    }

    public function testWritesMoreRowsThanOneBatch(): void
    {
        $rows = [];
        foreach (range(1, 150) as $i) {
            $rows['trojmiasto::' . $i] = ['name' => 'Operator ' . $i];
        }

        $this->assertSame(150, $this->upsert($this->newImport(), $rows));
        $this->assertSame(150, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM operator'));
    }

    public function testFailureRollsBackTheBatch(): void
    {
        $event = $this->newImport();

        try {
            $this->connection->transactional(fn () => $this->upsert($event, [
                'trojmiasto::1' => ['name' => 'Written'],
                'trojmiasto::2' => ['name' => 'Broken',
                    'no_such_column'       => 1],
            ]));
            $this->fail('The upsert should have failed.');
        } catch (\Doctrine\DBAL\Exception) {
        }

        $this->assertSame([], $this->rows('operator', 'id'));
    }

    private function upsert(DataUpdateEvent $event, array $rows): int
    {
        return new TableUpserter($this->connection)->upsert('operator', $rows, self::PROVIDER, $event->import);
    }
}
