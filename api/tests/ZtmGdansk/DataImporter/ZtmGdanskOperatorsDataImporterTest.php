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
use App\Provider\ZtmGdansk\DataImporter\ZtmGdanskOperatorsDataImporter;
use App\Service\IdUtils;
use App\Tests\DataImport\ImporterTestCase;
use App\Tests\DataImport\StubJsonStreamer;

final class ZtmGdanskOperatorsDataImporterTest extends ImporterTestCase
{
    public function testFirstImportInsertsRows(): void
    {
        $event = $this->newImport();

        $this->import($event, [$this->agency(1, 'ZTM Gdańsk')]);

        $this->assertSame([
            [
                'id'          => 'trojmiasto::1',
                'provider_id' => 'trojmiasto',
                'import_id'   => $event->import->getId()->toBinary(),
                'name'        => 'ZTM Gdańsk',
                'email'       => 'ztm@example.com',
                'url'         => 'https://ztm.example',
                'phone'       => '+48 58 520 57 75',
            ],
        ], $this->operators());
    }

    private function import(DataUpdateEvent $event, array $agencies): void
    {
        new ZtmGdanskOperatorsDataImporter(
            $this->connection,
            new TableUpserter($this->connection),
            new StubJsonStreamer(fn () => $agencies),
            new IdUtils(),
            $this->config(),
        )->import($this->reporter(), $event);
    }

    private function agency(int $id, string $name): array
    {
        return [
            'agencyId'    => $id,
            'agencyName'  => $name,
            'agencyEmail' => 'ztm@example.com',
            'agencyUrl'   => 'https://ztm.example',
            'agencyPhone' => '+48 58 520 57 75',
        ];
    }

    private function operators(): array
    {
        return $this->rows('operator', 'id', 'provider_id', 'import_id', 'name', 'email', 'url', 'phone');
    }
}
