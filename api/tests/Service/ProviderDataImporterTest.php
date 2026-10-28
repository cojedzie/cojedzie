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

namespace App\Tests\Service;

use App\Provider\Provider;
use App\Service\ProviderDataImporter;
use App\Service\ProviderResolver;
use App\Tests\DataImport\ImporterTestCase;

final class ProviderDataImporterTest extends ImporterTestCase
{
    public function testProvidersAreRegisteredWithTheCurrentTime(): void
    {
        $this->import(new ProviderResolver([$this->provider('trojmiasto')], false));

        $this->assertSame(
            [['trojmiasto', 'MZKZG - Trójmiasto', '2026-10-28 12:00:00']],
            $this->connection->fetchAllNumeric('SELECT id, name, update_date FROM provider ORDER BY id'),
        );
    }

    public function testDummyProviderIsNotRegisteredOutsideDebug(): void
    {
        $this->import(new ProviderResolver([$this->provider('trojmiasto'), $this->provider('dummy')], false));

        $this->assertFalse($this->connection->fetchOne("SELECT id FROM provider WHERE id = 'dummy'"));
    }

    private function import(ProviderResolver $providers): void
    {
        new ProviderDataImporter($this->connection, $providers, $this->clock())->import($this->reporter(), $this->newImport());
    }

    private function provider(string $id): Provider
    {
        return self::getContainer()->get(ProviderResolver::class)->resolve($id);
    }
}
