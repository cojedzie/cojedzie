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
use App\Event\DataUpdateEvent;
use App\Provider\ZtmGdansk\DataImporter\ZtmGdanskStopDataImporter;
use App\Service\IdUtils;
use App\Tests\DataImport\ImporterTestCase;
use App\Tests\DataImport\StubJsonStreamer;

final class ZtmGdanskStopDataImporterTest extends ImporterTestCase
{
    public function testFirstImportInsertsRows(): void
    {
        $event = $this->newImport();

        $this->import($event, [$this->stop(1001, 'Brama Wyżynna', '01')]);

        $this->assertSame([
            [
                'id'          => 'trojmiasto::1001',
                'provider_id' => 'trojmiasto',
                'import_id'   => $event->import->getId()->toBinary(),
                'name'        => 'Brama Wyżynna',
                'variant'     => '01',
                'latitude'    => 54.35,
                'longitude'   => 18.64,
                'on_demand'   => 0,
                'group_name'  => 'Brama Wyżynna',
            ],
        ], $this->stops());
    }

    public function testTechnicalStopsAreSkipped(): void
    {
        $this->import($this->newImport(), [
            [...$this->stop(1, 'Zajezdnia', '01'),
                'depot' => true],
            [...$this->stop(2, 'Techniczny', '01'),
                'nonpassenger' => true],
            [...$this->stop(3, 'Wirtualny', '01'),
                'virtual' => true],
        ]);

        $this->assertSame([], $this->stops());
    }

    public function testNameFallsBackToDescription(): void
    {
        $this->import($this->newImport(), [
            [...$this->stop(1, null, null),
                'stopDesc' => ' Oliwa ',
                'subName'  => '02'],
            [...$this->stop(2, 'Wrzeszcz', ''),
                'subName' => ''],
        ]);

        // name, variant, group name
        $this->assertSame([
            ['Oliwa', '02', 'Oliwa'],
            ['Wrzeszcz', null, 'Wrzeszcz'],
        ], $this->connection->fetchAllNumeric('SELECT name, variant, group_name FROM stop ORDER BY id'));
    }

    private function import(DataUpdateEvent $event, array $stops): void
    {
        new ZtmGdanskStopDataImporter(
            $this->connection,
            new TableUpserter($this->connection),
            new StubJsonStreamer(fn () => $stops),
            new IdUtils(),
            $this->config(),
            $this->clock(),
        )->import($this->reporter(), $event);
    }

    private function stop(int $id, ?string $name, ?string $code): array
    {
        return [
            'stopId'       => $id,
            'stopName'     => $name,
            'stopDesc'     => $name,
            'stopCode'     => $code,
            'subName'      => null,
            'stopLat'      => 54.35,
            'stopLon'      => 18.64,
            'onDemand'     => false,
            'nonpassenger' => false,
            'virtual'      => false,
            'depot'        => false,
        ];
    }

    private function stops(): array
    {
        return $this->rows('stop', 'id', 'provider_id', 'import_id', 'name', 'variant', 'latitude', 'longitude', 'on_demand', 'group_name');
    }
}
