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

namespace App\Tests\ZtmGdansk\DataImporter;

use App\DataImport\TableUpserter;
use App\Dto\Line;
use App\Event\DataUpdateEvent;
use App\Provider\ZtmGdansk\DataImporter\ZtmGdanskLineDataImporter;
use App\Service\IdUtils;
use App\Tests\DataImport\ImporterTestCase;
use App\Tests\DataImport\StubJsonStreamer;

final class ZtmGdanskLineDataImporterTest extends ImporterTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([1, 2, 5] as $agency) {
            $this->connection->insert('operator', [
                'id'          => self::PROVIDER . '::' . $agency,
                'provider_id' => self::PROVIDER,
                'name'        => 'Operator ' . $agency,
            ]);
        }
    }

    public function testFirstImportInsertsRows(): void
    {
        $event = $this->newImport();

        $this->import($event, [$this->route(10006, '6', 1)]);

        $this->assertSame([
            [
                'id'          => 'trojmiasto::10006',
                'provider_id' => 'trojmiasto',
                'import_id'   => $event->import->getId()->toBinary(),
                'operator_id' => 'trojmiasto::1',
                'symbol'      => '6',
                'type'        => Line::TYPE_BUS,
                'night'       => 0,
                'fast'        => 0,
            ],
        ], $this->lines());
    }

    public function testLineOfUnknownOperatorIsSkipped(): void
    {
        $this->import($this->newImport(), [$this->route(10006, '6', 99)]);

        $this->assertSame([], $this->lines());
    }

    public function testLineFlagsAndType(): void
    {
        $this->import($this->newImport(), [
            $this->route(1, 'N1', 1),
            $this->route(2, 'A', 1),
            $this->route(3, 'N', 1),
            $this->route(4, '2', 2),
            $this->route(5, '31', 5),
        ]);

        // symbol, type, night, fast
        $this->assertSame([
            ['N1', Line::TYPE_BUS, 1, 0],
            ['A', Line::TYPE_BUS, 0, 1],
            ['N', Line::TYPE_BUS, 0, 0],
            ['2', Line::TYPE_TRAM, 0, 0],
            ['31', Line::TYPE_TROLLEYBUS, 0, 0],
        ], $this->connection->fetchAllNumeric('SELECT symbol, type, night, fast FROM line ORDER BY id'));
    }

    private function import(DataUpdateEvent $event, array $routes): void
    {
        new ZtmGdanskLineDataImporter(
            $this->connection,
            new TableUpserter($this->connection),
            new StubJsonStreamer(fn () => $routes),
            new IdUtils(),
            $this->config(),
            $this->clock(),
        )->import($this->reporter(), $event);
    }

    private function route(int $id, string $symbol, int $agency): array
    {
        return [
            'routeId'        => $id,
            'routeShortName' => $symbol,
            'agencyId'       => $agency,
        ];
    }

    private function lines(): array
    {
        return $this->rows('line', 'id', 'provider_id', 'import_id', 'operator_id', 'symbol', 'type', 'night', 'fast');
    }
}
